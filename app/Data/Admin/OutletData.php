<?php

namespace App\Data\Admin;

use App\Data\OutletOptionData;
use App\Enums\OnboardingStatus;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An outlet as admins see it, including who approved or suspended it and why.
 * Load `business`, `hostOutlet`, `approvedBy` and `suspendedBy` first to avoid queries per outlet.
 */
#[MapName(SnakeCaseMapper::class)]
class OutletData extends Data
{
    public function __construct(
        public string $id,
        public string $businessId,
        public string $businessName,
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
        public ?CarbonInterface $approvedAt,
        public ?string $approvedByName,
        public ?string $rejectionReason,
        public ?CarbonInterface $suspendedAt,
        public ?string $suspendedByName,
        public ?string $suspensionReason,
        public ?CarbonInterface $archivedAt,
        public ?CarbonInterface $hiddenAt,
        public ?string $hiddenReason,
        public bool $isOperational,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Outlet $outlet): self
    {
        return new self(
            id: $outlet->id,
            businessId: $outlet->business_id,
            businessName: $outlet->business->name,
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
            approvedAt: $outlet->approved_at,
            approvedByName: $outlet->approvedBy?->name,
            rejectionReason: $outlet->rejection_reason,
            suspendedAt: $outlet->suspended_at,
            suspendedByName: $outlet->suspendedBy?->name,
            suspensionReason: $outlet->suspension_reason,
            archivedAt: $outlet->archived_at,
            hiddenAt: $outlet->hidden_at,
            hiddenReason: $outlet->hidden_reason,
            isOperational: $outlet->isOperational(),
            createdAt: $outlet->created_at,
        );
    }
}
