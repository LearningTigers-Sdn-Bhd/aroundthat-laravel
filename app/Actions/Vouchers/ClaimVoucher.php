<?php

namespace App\Actions\Vouchers;

use App\Enums\VoucherStatus;
use App\Models\Integration;
use App\Models\Outlet;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A partner claims a voucher of a published offer for one of its guests. The guest is known only by a hash of the
 * partner's own reference. A guest holds at most one live voucher of an offer per partner, and the partner's
 * idempotency key makes a retried claim return the same voucher. Claims for one key or one guest run one at a time.
 */
class ClaimVoucher
{
    public function __construct(protected IssueVoucher $issueVoucher) {}

    /**
     * @return array{status: 'created'|'existing_active'|'idempotent_replay'|'idempotency_conflict', voucher: Voucher|null, code: string|null}
     *
     * @throws ValidationException When the offer's limit is reached (VoucherLimitReached) or the offer cannot issue.
     */
    public function handle(Integration $integration, VoucherOffer $offer, string $guestRef, string $claimKey, ?Outlet $outlet = null): array
    {
        $guestRefHmac = self::guestRefHmac($integration, $guestRef);

        return DB::transaction(function () use ($integration, $offer, $guestRefHmac, $claimKey, $outlet): array {
            $this->lock("claim-key:{$integration->getKey()}:{$claimKey}");
            $this->lock("claim-guest:{$integration->getKey()}:{$guestRefHmac}:{$offer->getKey()}");

            $claimed = Voucher::query()
                ->where('integration_id', $integration->getKey())
                ->where('claim_key', $claimKey)
                ->first();

            if ($claimed !== null) {
                $sameRequest = $claimed->voucher_offer_id === $offer->getKey()
                    && $claimed->guest_ref_hmac === $guestRefHmac
                    && $claimed->outlet_id === $outlet?->getKey();

                return $sameRequest
                    ? ['status' => 'idempotent_replay', 'voucher' => $claimed, 'code' => $claimed->code]
                    : ['status' => 'idempotency_conflict', 'voucher' => null, 'code' => null];
            }

            $live = Voucher::query()
                ->where('integration_id', $integration->getKey())
                ->where('guest_ref_hmac', $guestRefHmac)
                ->where('voucher_offer_id', $offer->getKey())
                ->where('status', VoucherStatus::Active)
                ->where('expires_at', '>', now())
                ->first();

            if ($live !== null) {
                return ['status' => 'existing_active', 'voucher' => $live, 'code' => null];
            }

            [$voucher, $code] = $this->issueVoucher->handle($offer, [
                'integration_id' => $integration->getKey(),
                'guest_ref_hmac' => $guestRefHmac,
                'claim_key' => $claimKey,
                'outlet_id' => $outlet?->getKey(),
            ]);

            return ['status' => 'created', 'voucher' => $voucher, 'code' => $code];
        });
    }

    /**
     * The guest reference as stored: a keyed hash, scoped to the partner, so it cannot be read back or matched
     * across partners.
     */
    public static function guestRefHmac(Integration $integration, string $guestRef): string
    {
        $key = hash_hmac('sha256', 'voucher-guest-hmac-v1', (string) config('app.key'), true);

        return hash_hmac('sha256', "{$integration->getKey()}:{$guestRef}", $key);
    }

    /**
     * Wait for other claims holding the same lock until this transaction ends.
     */
    protected function lock(string $name): void
    {
        DB::select('select pg_advisory_xact_lock(hashtextextended(?, 0))', [$name]);
    }
}
