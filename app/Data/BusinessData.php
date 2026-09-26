<?php

namespace App\Data;

use App\Enums\OnboardingStatus;
use App\Models\Business;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A business as its own members see it. Who approved or suspended it, and why it was suspended, stay with admins.
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
        public ?string $rejectionReason,
        public bool $isSuspended,
        public bool $isWritable,
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
            rejectionReason: $business->rejection_reason,
            isSuspended: $business->isSuspended(),
            isWritable: $business->isWritable(),
        );
    }
}
