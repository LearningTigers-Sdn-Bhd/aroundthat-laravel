<?php

namespace App\Data;

use App\Data\Forms\OfferFormData;
use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * A voucher offer as its business's members see it. Load `business` and `outlets.business` first.
 * The `*_local` dates are in the business's time zone, for the form.
 */
#[MapName(SnakeCaseMapper::class)]
class OfferData extends Data
{
    /**
     * @param  string  $state  What matters most right now; see VoucherOffer::state().
     * @param  list<OfferOutletData>  $outlets
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public DiscountType $discountType,
        public ?string $discountValue,
        public ?string $maxDiscountAmount,
        public ?string $minSpendAmount,
        public ?string $freeItem,
        public string $currency,
        public CarbonInterface $startsAt,
        public CarbonInterface $endsAt,
        public string $startsAtLocal,
        public string $endsAtLocal,
        public ?int $voucherValidDays,
        public ?int $voucherLimit,
        public int $issuedCount,
        public int $usesPerVoucher,
        public OfferStatus $status,
        #[LiteralTypeScriptType("'draft' | 'active' | 'paused' | 'scheduled' | 'ended' | 'hidden'")]
        public string $state,
        public bool $isLocked,
        public ?string $hiddenReason,
        public array $outlets,
    ) {}

    public static function fromModel(VoucherOffer $offer): self
    {
        $timezone = $offer->business->timezone;

        return new self(
            id: $offer->id,
            name: $offer->name,
            description: $offer->description,
            discountType: $offer->discount_type,
            discountValue: $offer->discount_value,
            maxDiscountAmount: $offer->max_discount_amount,
            minSpendAmount: $offer->min_spend_amount,
            freeItem: $offer->free_item,
            currency: config('vouchers.currency'),
            startsAt: $offer->starts_at,
            endsAt: $offer->ends_at,
            startsAtLocal: $offer->starts_at->copy()->setTimezone($timezone)->format(OfferFormData::DATE_TIME_FORMAT),
            endsAtLocal: $offer->ends_at->copy()->setTimezone($timezone)->format(OfferFormData::DATE_TIME_FORMAT),
            voucherValidDays: $offer->voucher_valid_days,
            voucherLimit: $offer->voucher_limit,
            issuedCount: $offer->issued_count,
            usesPerVoucher: $offer->uses_per_voucher,
            status: $offer->status,
            state: $offer->state(),
            isLocked: $offer->isLocked(),
            hiddenReason: $offer->hidden_reason,
            outlets: array_values($offer->outlets->map(fn (Outlet $outlet): OfferOutletData => OfferOutletData::fromModel($outlet, $offer))->all()),
        );
    }
}
