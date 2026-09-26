<?php

namespace App\Data;

use App\Models\Redemption;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A recent redemption on the dashboard. Load `voucher.offer` and `outlet` first.
 */
#[MapName(SnakeCaseMapper::class)]
class DashboardRedemptionData extends Data
{
    public function __construct(
        public string $id,
        public string $offerName,
        public string $outletName,
        public string $billAmount,
        public string $discountAmount,
        public string $currency,
        public CarbonInterface $redeemedAt,
        public bool $isCancelled,
    ) {}

    public static function fromModel(Redemption $redemption): self
    {
        return new self(
            id: $redemption->id,
            offerName: $redemption->voucher->offer->name,
            outletName: $redemption->outlet->name,
            billAmount: $redemption->bill_amount,
            discountAmount: $redemption->discount_amount,
            currency: config('vouchers.currency'),
            redeemedAt: $redemption->redeemed_at,
            isCancelled: $redemption->isCancelled(),
        );
    }
}
