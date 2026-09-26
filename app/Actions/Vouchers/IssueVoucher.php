<?php

namespace App\Actions\Vouchers;

use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use App\Support\Vouchers\VoucherCode;
use App\Support\Vouchers\VoucherLimitReached;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issue one voucher of an offer with a fresh code. The offer's counter goes up in the same statement that checks
 * the limit, so two issues at once can never pass it. The voucher expires after the offer's `voucher_valid_days`,
 * or with the offer, whichever comes first.
 */
class IssueVoucher
{
    public const int CODE_ATTEMPTS = 3;

    public function __construct(protected AuditTrail $audit) {}

    /**
     * @param  array<string, mixed>  $attributes  Partner claim fields, such as `integration_id` and `guest_ref_hmac`.
     * @return array{0: Voucher, 1: string} The voucher and its plain code, which is shown once.
     *
     * @throws ValidationException
     */
    public function handle(VoucherOffer $offer, array $attributes = []): array
    {
        return DB::transaction(function () use ($offer, $attributes): array {
            $offer->loadMissing('business');

            $problem = match (true) {
                ! $offer->business->isApproved() || $offer->business->isSuspended() => __('Vouchers can be issued once an admin approves the business, and not while it is suspended.'),
                $offer->isHidden() => __('An admin hid this offer, so no vouchers can be issued.'),
                $offer->hasEnded() => __('This offer has ended.'),
                default => null,
            };

            if ($problem !== null) {
                throw ValidationException::withMessages(['offer' => $problem]);
            }

            $this->reserve($offer);

            return $this->audit->as('issued', null, fn (): array => $this->create($offer, $attributes));
        });
    }

    /**
     * Count the voucher against the offer's limit, or stop when it is full.
     *
     * @throws ValidationException
     */
    protected function reserve(VoucherOffer $offer): void
    {
        $reserved = VoucherOffer::query()
            ->whereKey($offer->getKey())
            ->underVoucherLimit()
            ->increment('issued_count');

        if ($reserved === 0) {
            throw VoucherLimitReached::withMessages(['offer' => __('Every voucher of this offer has been issued.')]);
        }

        $offer->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Voucher, 1: string}
     */
    protected function create(VoucherOffer $offer, array $attributes): array
    {
        $expiresAt = $offer->voucher_valid_days === null
            ? $offer->ends_at
            : now()->addDays($offer->voucher_valid_days)->min($offer->ends_at);

        for ($attempt = 1; ; $attempt++) {
            $code = VoucherCode::generate();

            try {
                $voucher = DB::transaction(fn (): Voucher => $offer->vouchers()->forceCreate([
                    ...$attributes,
                    'code' => $code,
                    'code_hash' => VoucherCode::hash($code),
                    'code_prefix' => VoucherCode::prefix($code),
                    'expires_at' => $expiresAt,
                ]));

                return [$voucher, $code];
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::CODE_ATTEMPTS || ! str_contains($exception->getMessage(), 'vouchers_code_hash_unique')) {
                    throw $exception;
                }
            }
        }
    }
}
