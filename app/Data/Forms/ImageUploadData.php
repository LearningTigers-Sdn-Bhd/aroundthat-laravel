<?php

namespace App\Data\Forms;

use App\Actions\Images\ManageImages;
use App\Enums\ImageKind;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A photo or logo being uploaded, with the alt text that describes it to visitors using screen readers.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class ImageUploadData extends Data
{
    public function __construct(
        public UploadedFile $file,
        public ImageKind $kind,
        #[Max(250)]
        public string $altText,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'file' => ['file', 'mimetypes:'.implode(',', ManageImages::CONTENT_TYPES), 'max:'.ManageImages::MAX_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'file.mimetypes' => __('Upload a JPEG, PNG or WebP image.'),
            'file.max' => __('The image can be at most 10 MB.'),
        ];
    }
}
