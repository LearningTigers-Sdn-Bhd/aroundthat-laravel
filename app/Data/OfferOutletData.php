<?php

namespace App\Data;

use App\Models\Outlet;
use App\Models\VoucherOffer;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An outlet where an offer's vouchers can be redeemed. A sponsored outlet belongs to another business.
 */
#[MapName(SnakeCaseMapper::class)]
class OfferOutletData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $isSponsored,
        public ?string $businessName,
    ) {}

    /**
     * Load the outlet's `business` first when it may be sponsored.
     */
    public static function fromModel(Outlet $outlet, VoucherOffer $offer): self
    {
        $isSponsored = $offer->isSponsoredOutlet($outlet);

        return new self(
            id: $outlet->id,
            name: $outlet->name,
            isSponsored: $isSponsored,
            businessName: $isSponsored ? $outlet->business->name : null,
        );
    }
}
