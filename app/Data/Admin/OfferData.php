<?php

namespace App\Data\Admin;

use App\Data\OfferData as OwnerOfferData;
use App\Models\VoucherOffer;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A voucher offer as admins see it: the owner's view plus its business and when it was hidden.
 * Load `business` and `outlets.business` first.
 */
#[MapName(SnakeCaseMapper::class)]
class OfferData extends Data
{
    public function __construct(
        public OwnerOfferData $offer,
        public string $businessId,
        public string $businessName,
        public bool $isSponsored,
        public ?CarbonInterface $hiddenAt,
    ) {}

    public static function fromModel(VoucherOffer $offer): self
    {
        $data = OwnerOfferData::fromModel($offer);

        return new self(
            offer: $data,
            businessId: $offer->business_id,
            businessName: $offer->business->name,
            isSponsored: collect($data->outlets)->contains('isSponsored', true),
            hiddenAt: $offer->hidden_at,
        );
    }
}
