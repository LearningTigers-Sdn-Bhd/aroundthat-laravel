<?php

namespace App\Data;

use App\Enums\OnboardingStatus;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An outlet as its business's members see it. Load `business` and `hostOutlet` first to avoid a query per outlet.
 */
#[MapName(SnakeCaseMapper::class)]
class OutletData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $contactEmail,
        public ?string $contactPhone,
        #[MapName('address_line_1')]
        public string $addressLine1,
        #[MapName('address_line_2')]
        public ?string $addressLine2,
        public string $city,
        public string $state,
        public string $postcode,
        public string $countryCode,
        public string $timezone,
        public ?OutletOptionData $hostOutlet,
        public OnboardingStatus $onboardingStatus,
        public ?CarbonInterface $submittedAt,
        public ?string $rejectionReason,
        public bool $isSuspended,
        public ?CarbonInterface $archivedAt,
        public bool $isOperational,
        public bool $isWritable,
        public bool $isPublic,
    ) {}

    public static function fromModel(Outlet $outlet): self
    {
        return new self(
            id: $outlet->id,
            name: $outlet->name,
            contactEmail: $outlet->contact_email,
            contactPhone: $outlet->contact_phone,
            addressLine1: $outlet->address_line_1,
            addressLine2: $outlet->address_line_2,
            city: $outlet->city,
            state: $outlet->state,
            postcode: $outlet->postcode,
            countryCode: $outlet->country_code,
            timezone: $outlet->timezone,
            hostOutlet: $outlet->hostOutlet ? OutletOptionData::fromModel($outlet->hostOutlet) : null,
            onboardingStatus: $outlet->onboarding_status,
            submittedAt: $outlet->submitted_at,
            rejectionReason: $outlet->rejection_reason,
            isSuspended: $outlet->isSuspended(),
            archivedAt: $outlet->archived_at,
            isOperational: $outlet->isOperational(),
            isWritable: $outlet->isWritable(),
            isPublic: $outlet->isPublic(),
        );
    }
}
