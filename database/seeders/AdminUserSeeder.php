<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds. Skipped when ADMIN_SEED_EMAIL or ADMIN_SEED_PASSWORD is not set.
     */
    public function run(): void
    {
        $email = config('seeding.admin_email');
        $password = config('seeding.admin_password');

        if (blank($email) || blank($password)) {
            $this->command->warn('Skipping Admin account: ADMIN_SEED_EMAIL or ADMIN_SEED_PASSWORD is not set.');

            return;
        }

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Set a valid ADMIN_SEED_EMAIL before seeding the Admin account.');
        }

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set ADMIN_SEED_PASSWORD to at least 12 characters before seeding the Admin account.');
        }

        if (User::where('email', $email)->exists()) {
            return;
        }

        (new User)->forceFill([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_admin' => true,
        ])->save();
    }
}
