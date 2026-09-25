<?php

namespace App\Data\Forms;

use App\Models\Tag;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * How visitors read about an outlet, beyond the name, address and contacts it already has.
 * Tags are names, so owners can type new ones.
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
        public ?array $tags = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
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
            'tags.max' => __('An outlet can have at most :max tags.'),
        ];
    }
}
