<?php

namespace App\Data;

use App\Models\Redemption;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A redemption on the counter's list for today. Load `voucher.offer`, `user` and `cancelledBy` first.
 */
#[MapName(SnakeCaseMapper::class)]
class RedemptionData extends Data
{
    public function __construct(
        public string $id,
        public string $offerName,
        public string $codePrefix,
        public string $billAmount,
        public string $discountAmount,
        public string $netAmount,
        public string $currency,
        public CarbonInterface $redeemedAt,
        public ?string $cashierName,
        public ?CarbonInterface $cancelledAt,
        public ?string $cancelledByName,
        public ?string $cancelReason,
        public bool $canCancel,
    ) {}

    public static function fromModel(Redemption $redemption): self
    {
        return new self(
            id: $redemption->id,
            offerName: $redemption->voucher->offer->name,
            codePrefix: $redemption->voucher->code_prefix,
            billAmount: $redemption->bill_amount,
            discountAmount: $redemption->discount_amount,
            netAmount: $redemption->netAmount(),
            currency: config('vouchers.currency'),
            redeemedAt: $redemption->redeemed_at,
            cashierName: $redemption->user?->name,
            cancelledAt: $redemption->cancelled_at,
            cancelledByName: $redemption->cancelledBy?->name,
            cancelReason: $redemption->cancel_reason,
            canCancel: $redemption->isCancellable(),
        );
    }
}
