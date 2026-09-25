<?php

use App\Actions\Vouchers\IssueVoucher;
use App\Enums\VoucherStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\VoucherOffer;
use App\Support\Vouchers\VoucherCode;
use Illuminate\Validation\ValidationException;

test('issuing a voucher stores the code hashed and encrypted and counts it against the offer', function () {
    $offer = VoucherOffer::factory()->active()->create();

    [$voucher, $code] = app(IssueVoucher::class)->handle($offer);

    expect(VoucherCode::isWellFormed($code))->toBeTrue()
        ->and($voucher->status)->toBe(VoucherStatus::Active)
        ->and($voucher->code_hash)->toBe(VoucherCode::hash($code))
        ->and($voucher->code_prefix)->toBe(substr($code, 0, 4))
        ->and($voucher->refresh()->code)->toBe($code)
        ->and($voucher->getRawOriginal('code'))->not->toContain($code)
        ->and($offer->refresh()->issued_count)->toBe(1);
    expect(Activity::forSubject($voucher)->forEvent('issued')->exists())->toBeTrue();
});

test('a voucher expires after the offer valid days, but never after the offer ends', function (?int $days, string $expected) {
    $this->freezeTime();
    $offer = VoucherOffer::factory()->active()->create(['ends_at' => now()->addDays(10), 'voucher_valid_days' => $days]);

    [$voucher] = app(IssueVoucher::class)->handle($offer);

    expect($voucher->expires_at->toIso8601String())->toBe(now()->modify($expected)->toIso8601String());
})->with([
    'no valid days' => [null, '+10 days'],
    'shorter than the offer' => [3, '+3 days'],
    'longer than the offer' => [30, '+10 days'],
]);

test('no voucher is issued past the offer limit', function () {
    $offer = VoucherOffer::factory()->active()->create(['voucher_limit' => 2]);
    $issue = app(IssueVoucher::class);

    $issue->handle($offer);
    $issue->handle($offer);

    expect(fn () => $issue->handle($offer))->toThrow(ValidationException::class, 'Every voucher of this offer has been issued.');
    expect($offer->refresh()->issued_count)->toBe(2)
        ->and($offer->vouchers()->count())->toBe(2);
});

test('no voucher is issued for a hidden or ended offer, or a business that cannot trade', function (string $case) {
    $offer = match ($case) {
        'hidden' => VoucherOffer::factory()->active()->hidden()->create(),
        'ended' => VoucherOffer::factory()->active()->ended()->create(),
        'business suspended' => VoucherOffer::factory()->for(Business::factory()->approved()->suspended())->create(),
        'business pending' => VoucherOffer::factory()->for(Business::factory()->pending())->create(),
    };

    expect(fn () => app(IssueVoucher::class)->handle($offer))->toThrow(ValidationException::class);
    expect($offer->refresh()->issued_count)->toBe(0);
})->with(['hidden', 'ended', 'business suspended', 'business pending']);
