<?php

namespace App\Data\Admin;

use App\Enums\OnboardingStatus;
use App\Models\Business;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A business as admins see it, including who approved or suspended it and why.
 * Load `approvedBy` and `suspendedBy` first to avoid queries per business.
 */
#[MapName(SnakeCaseMapper::class)]
class BusinessData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $registeredName,
        public ?string $registrationNumber,
        public string $contactEmail,
        public ?string $contactPhone,
        public ?string $address,
        public string $timezone,
        public OnboardingStatus $onboardingStatus,
        public ?CarbonInterface $submittedAt,
        public ?CarbonInterface $approvedAt,
        public ?string $approvedByName,
        public ?string $rejectionReason,
        public ?CarbonInterface $suspendedAt,
        public ?string $suspendedByName,
        public ?string $suspensionReason,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Business $business): self
    {
        return new self(
            id: $business->id,
            name: $business->name,
            registeredName: $business->registered_name,
            registrationNumber: $business->registration_number,
            contactEmail: $business->contact_email,
            contactPhone: $business->contact_phone,
            address: $business->address,
            timezone: $business->timezone,
            onboardingStatus: $business->onboarding_status,
            submittedAt: $business->submitted_at,
            approvedAt: $business->approved_at,
            approvedByName: $business->approvedBy?->name,
            rejectionReason: $business->rejection_reason,
            suspendedAt: $business->suspended_at,
            suspendedByName: $business->suspendedBy?->name,
            suspensionReason: $business->suspension_reason,
            createdAt: $business->created_at,
        );
    }
}
