<?php

namespace App\Data\Forms;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;

/**
 * A password an admin sets for a locked-out user and passes on to them. The user must change it at their next login.
 */
class TemporaryPasswordData extends Data
{
    public function __construct(
        public string $password,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults()],
        ];
    }
}
