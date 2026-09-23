<?php

use App\Actions\Users\ChangeUserStatus;
use App\Models\Activity;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('suspends and reactivates a user with the reason logged', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    app(ChangeUserStatus::class)->suspend($admin, $user, 'Shared their login.');

    expect($user->refresh()->isSuspended())->toBeTrue();
    expect(Activity::forSubject($user)->where('event', 'suspended')->sole()->reason)->toBe('Shared their login.');

    app(ChangeUserStatus::class)->reactivate($user);

    expect($user->refresh()->isSuspended())->toBeFalse();
});

test('admins cannot suspend themselves', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    app(ChangeUserStatus::class)->suspend($admin, $admin, 'Oops.');
})->throws(ValidationException::class);

test('refuses to suspend the last active admin', function () {
    $actingAdmin = User::factory()->admin()->create();
    $lastOtherAdmin = User::factory()->admin()->create();
    $actingAdmin->forceFill(['suspended_at' => now()])->save();

    expect(fn () => app(ChangeUserStatus::class)->suspend($actingAdmin, $lastOtherAdmin, 'No.'))
        ->toThrow(ValidationException::class);
    expect($lastOtherAdmin->refresh()->isSuspended())->toBeFalse();
});

test('refuses to suspend the only owner of a business', function () {
    $admin = User::factory()->admin()->create();
    $owner = Membership::factory()->owner()->create();

    expect(fn () => app(ChangeUserStatus::class)->suspend($admin, $owner->user, 'No.'))
        ->toThrow(ValidationException::class);
    expect($owner->user->refresh()->isSuspended())->toBeFalse();
});
