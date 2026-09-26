<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Timezone;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The business details an owner or admin can edit.
 */
#[MapName(SnakeCaseMapper::class)]
class BusinessDetailsData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $name,
        #[Max(255), Email]
        public string $contactEmail,
        #[Timezone]
        public string $timezone = 'Asia/Kuala_Lumpur',
        #[Max(255)]
        public ?string $registeredName = null,
        #[Max(100)]
        public ?string $registrationNumber = null,
        #[Max(50)]
        public ?string $contactPhone = null,
        #[Max(1000)]
        public ?string $address = null,
    ) {}

    /**
     * The values as model attributes.
     *
     * @return array<string, string|null>
     */
    public function toModelAttributes(): array
    {
        return [
            'name' => $this->name,
            'contact_email' => $this->contactEmail,
            'timezone' => $this->timezone,
            'registered_name' => $this->registeredName,
            'registration_number' => $this->registrationNumber,
            'contact_phone' => $this->contactPhone,
            'address' => $this->address,
        ];
    }
}
