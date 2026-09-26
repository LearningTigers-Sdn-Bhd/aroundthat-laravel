<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The outlet's website, WhatsApp number and social pages.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class OutletLinksData extends Data
{
    public function __construct(
        #[Max(500)]
        public ?string $website = null,
        #[Max(30)]
        public ?string $whatsapp = null,
        #[Max(500)]
        public ?string $facebook = null,
        #[Max(500)]
        public ?string $instagram = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $link = ['url:http,https'];

        return [
            'website' => $link,
            'facebook' => $link,
            'instagram' => $link,
            'whatsapp' => ['regex:/^\+?[0-9][0-9 ()-]{5,19}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'whatsapp.regex' => __('The WhatsApp number must be a phone number, such as +60 12-345 6789.'),
        ];
    }
}
