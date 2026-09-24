<?php

namespace App\Data\Forms;

use App\Models\Tag;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What visitors see about an outlet, beyond the name, address and contacts it already has.
 * A pasted Google Maps link fills the coordinates; tags are names, so owners can type new ones.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class OutletPublicProfileData extends Data
{
    /**
     * @param  list<string>|null  $tags  Null when every tag was removed, because an empty list sends nothing.
     */
    public function __construct(
        #[Max(280)]
        public ?string $summary = null,
        #[Max(5000)]
        public ?string $description = null,
        #[Uuid]
        public ?string $categoryId = null,
        #[Max(2000)]
        public ?string $googleMapsUrl = null,
        #[Between(-90, 90), RequiredWith('longitude')]
        public ?float $latitude = null,
        #[Between(-180, 180), RequiredWith('latitude')]
        public ?float $longitude = null,
        #[Max(500)]
        public ?string $website = null,
        #[Max(30)]
        public ?string $whatsapp = null,
        #[Max(500)]
        public ?string $facebook = null,
        #[Max(500)]
        public ?string $instagram = null,
        public ?array $tags = null,
        public bool $isListed = false,
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
            'tags' => ['array', 'max:'.Tag::MAX_PER_OUTLET],
            'tags.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'whatsapp.regex' => __('The WhatsApp number must be a phone number, such as +60 12-345 6789.'),
            'tags.max' => __('An outlet can have at most :max tags.'),
        ];
    }
}
