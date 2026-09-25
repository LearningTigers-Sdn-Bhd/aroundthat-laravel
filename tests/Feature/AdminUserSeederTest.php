<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('creates a verified Admin login with a hashed configured password', function () {
    config()->set('seeding.admin_email', 'admin@example.com');
    config()->set('seeding.admin_password', 'long-secret-password');

    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect($admin->name)->toBe('Admin');
    expect($admin->is_admin)->toBeTrue();
    expect($admin->hasVerifiedEmail())->toBeTrue();
    expect(Hash::check('long-secret-password', $admin->password))->toBeTrue();
});

test('does not duplicate or reset an existing account', function () {
    config()->set('seeding.admin_email', 'admin@example.com');
    config()->set('seeding.admin_password', 'long-secret-password');
    $existingUser = User::factory()->create([
        'name' => 'Existing User',
        'email' => 'admin@example.com',
    ]);

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@example.com')->count())->toBe(1);
    expect($existingUser->fresh()->name)->toBe('Existing User');
    expect(Hash::check('password', $existingUser->fresh()->password))->toBeTrue();
});

test('skips the Admin account when the email or password is not set', function (?string $email, ?string $password) {
    config()->set('seeding.admin_email', $email);
    config()->set('seeding.admin_password', $password);

    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(0);
})->with([
    'no email' => [null, 'long-secret-password'],
    'no password' => ['admin@example.com', null],
    'empty password' => ['admin@example.com', ''],
]);

test('requires a long enough password before creating an account', function () {
    config()->set('seeding.admin_email', 'admin@example.com');
    config()->set('seeding.admin_password', 'short');

    expect(fn () => $this->seed(AdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'Set ADMIN_SEED_PASSWORD');

    expect(User::count())->toBe(0);
});

test('rejects an invalid Admin email', function () {
    config()->set('seeding.admin_email', 'not-an-email');
    config()->set('seeding.admin_password', 'long-secret-password');

    expect(fn () => $this->seed(AdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'Set a valid ADMIN_SEED_EMAIL');

    expect(User::count())->toBe(0);
});
