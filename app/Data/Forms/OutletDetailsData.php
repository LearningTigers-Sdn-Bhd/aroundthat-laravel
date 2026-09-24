<?php

namespace App\Data\Forms;

use App\Support\Locations;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Timezone;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * The outlet details an owner or admin can edit.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class OutletDetailsData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $name,
        #[Max(255), MapName('address_line_1')]
        public string $addressLine1,
        #[Max(255)]
        public string $city,
        #[Max(255)]
        public string $state,
        #[Max(20)]
        public string $postcode,
        #[In(Locations::COUNTRY_CODES)]
        public string $countryCode = 'MY',
        #[Timezone]
        public string $timezone = 'Asia/Kuala_Lumpur',
        #[Max(255), MapName('address_line_2')]
        public ?string $addressLine2 = null,
        #[Max(255), Email]
        public ?string $contactEmail = null,
        #[Max(50)]
        public ?string $contactPhone = null,
    ) {}

    /**
     * A Malaysian outlet must name one of its states or federal territories.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        if (($context->payload['country_code'] ?? null) !== 'MY') {
            return [];
        }

        return [
            'state' => [Rule::in(Locations::MALAYSIAN_STATES)],
        ];
    }

    /**
     * The values as model attributes.
     *
     * @return array<string, string|null>
     */
    public function toModelAttributes(): array
    {
        return [
            'name' => $this->name,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'country_code' => $this->countryCode,
            'timezone' => $this->timezone,
            'contact_email' => $this->contactEmail,
            'contact_phone' => $this->contactPhone,
        ];
    }
}
