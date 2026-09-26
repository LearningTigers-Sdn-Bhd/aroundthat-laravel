<?php

use App\Actions\Users\RecoverUserAccess;
use App\Models\Activity;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Admin']);
});

test('users can be filtered by search, suspension and admin flag', function (array $filter, string $expected) {
    User::factory()->create(['name' => 'Aminah', 'email' => 'aminah@borneo.test']);
    User::factory()->suspended()->create(['name' => 'Suspended Sam']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['filter' => $filter]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.name', $expected));
})->with([
    'search by email' => [['search' => 'BORNEO.test'], 'Aminah'],
    'suspended' => [['suspended' => 'true'], 'Suspended Sam'],
    'admins' => [['is_admin' => 'true'], 'Admin'],
]);

test('a user page splits its details, businesses and history into tabs', function () {
    $membership = Membership::factory()->cashier()->create();
    $membership->user->update(['name' => 'Renamed']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $membership->user))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/show')
            ->where('user.id', $membership->user_id));

    $this->get(route('admin.users.businesses.index', $membership->user))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/businesses')
            ->where('memberships.0.business_id', $membership->business_id)
            ->where('memberships.0.role', 'cashier'));

    $this->get(route('admin.users.recovery.index', $membership->user))
        ->assertInertia(fn (Assert $page) => $page->component('admin/users/recovery'));

    $this->get(route('admin.users.activity.index', $membership->user))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/activity')
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('activities.0.changes.0.new', 'Renamed')));
});

test('an admin suspends and reactivates a login', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.users.suspend', $user), ['reason' => 'Fraud report.'])->assertSessionHasNoErrors();
    expect($user->refresh()->suspension_reason)->toBe('Fraud report.');

    $this->post(route('admin.users.reactivate', $user))->assertSessionHasNoErrors();
    expect($user->refresh()->isSuspended())->toBeFalse();
});

test('suspending a login needs a reason', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.users.suspend', $user))->assertSessionHasErrors('reason');
});

test('an admin cannot suspend their own login', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.suspend', $this->admin), ['reason' => 'Testing.'])
        ->assertSessionHasErrors(['user' => 'You cannot suspend your own login.']);
});

test('an admin emails a user a password reset link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.recovery.password-reset', $user))
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
    expect(Activity::forSubject($user)->where('event', 'password_reset_sent')->exists())->toBeTrue();
});

test('an admin sets a temporary password that the user must change', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.recovery.temporary-password', $user), ['password' => 'Temporary-Pass-2026'])
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect(Hash::check('Temporary-Pass-2026', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue();

    $activity = Activity::forSubject($user)->where('event', 'temporary_password_set')->sole();
    expect(json_encode($activity->properties))->not->toContain('Temporary-Pass-2026');
});

test('a temporary password signs the user out of their open session', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    app(RecoverUserAccess::class)->setTemporaryPassword($this->admin, $user, 'Temporary-Pass-2026');

    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a temporary password needs a password', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.recovery.temporary-password', $user))
        ->assertSessionHasErrors('password');
});

test('an admin turns off two-factor authentication for a user', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.recovery.two-factor-reset', $user))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->two_factor_secret)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

test('recovery is refused for suspended logins and the admin\'s own login', function (Closure $target, string $message) {
    $this->actingAs($this->admin)
        ->post(route('admin.users.recovery.password-reset', $target($this->admin)))
        ->assertSessionHasErrors(['user' => $message]);
})->with([
    'suspended' => [fn () => User::factory()->suspended()->create(), 'Reactivate this login first.'],
    'own login' => [fn (User $admin) => $admin, 'Change your own login from Settings.'],
]);
