<?php

use App\Models\Membership;
use App\Models\User;
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

test('a user page lists their businesses and history', function () {
    $membership = Membership::factory()->cashier()->create();
    $membership->user->update(['name' => 'Renamed']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $membership->user))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/show')
            ->where('user.id', $membership->user_id)
            ->where('memberships.0.business_id', $membership->business_id)
            ->where('memberships.0.role', 'cashier')
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
