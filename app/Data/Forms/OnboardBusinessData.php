<?php

namespace App\Data\Forms;

use App\Enums\OwnerMethod;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An admin creating a business with its first owner.
 */
#[MapName(SnakeCaseMapper::class)]
class OnboardBusinessData extends Data
{
    public function __construct(
        public BusinessDetailsData $business,
        public OwnerMethod $ownerMethod,
        #[Max(255), Email]
        public string $ownerEmail,
        #[RequiredIf('owner_method', 'temporary_password'), Max(255)]
        public ?string $ownerName = null,
        #[RequiredIf('owner_method', 'temporary_password')]
        public ?string $ownerPassword = null,
        public bool $approveImmediately = false,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'owner_password' => ['nullable', Password::defaults()],
        ];
    }
}
