<?php

namespace App\Data\Forms;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

/**
 * The name and password someone chooses when an invitation creates their login.
 */
class NewAccountData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $name,
        public string $password,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
