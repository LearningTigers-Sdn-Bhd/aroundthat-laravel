<?php

namespace App\Data;

use App\Models\Image;
use App\Models\Outlet;
use App\Support\OpeningHours;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An outlet as a visitor would see it: its details, public fields, hours, photos and business, in one place.
 */
#[MapName(SnakeCaseMapper::class)]
class PlacePreviewData extends Data
{
    /**
     * @param  list<ImageData>  $images
     */
    public function __construct(
        public string $name,
        public string $address,
        public ?string $contactPhone,
        public ?string $contactEmail,
        public PlaceProfileData $place,
        public OutletHoursData $hours,
        public array $images,
        public string $businessName,
        public BusinessPlaceData $business,
        public bool $isOpenNow,
        public ?string $closesAt,
        public ?string $nextOpensAt,
    ) {}

    public static function fromModel(Outlet $outlet, CarbonInterface $now): self
    {
        $outlet->loadMissing(['business', 'category', 'tags', 'dateExceptions']);
        $openState = OpeningHours::openState($outlet, $now);

        return new self(
            name: $outlet->name,
            address: collect([
                $outlet->address_line_1,
                $outlet->address_line_2,
                trim("{$outlet->postcode} {$outlet->city}"),
                $outlet->state,
            ])->filter()->join(', '),
            contactPhone: $outlet->contact_phone,
            contactEmail: $outlet->contact_email,
            place: PlaceProfileData::fromModel($outlet),
            hours: OutletHoursData::fromModel($outlet, $now->copy()->setTimezone($outlet->timezone)->toDateString()),
            images: array_values($outlet->images()->active()->orderBy('kind')->orderBy('position')->get()
                ->map(fn (Image $image): ImageData => ImageData::fromModel($image))
                ->all()),
            businessName: $outlet->business->name,
            business: BusinessPlaceData::fromModel($outlet->business),
            isOpenNow: $openState['is_open'],
            closesAt: $openState['closes_at'],
            nextOpensAt: $openState['next_opens_at'],
        );
    }
}
