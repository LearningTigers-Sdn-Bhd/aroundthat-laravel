<?php

use App\Http\Middleware\ResolveCurrentBusiness;
use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('users with one business open it without choosing', function () {
    $membership = Membership::factory()->cashier()->create();

    $response = $this->actingAs($membership->user)->get(route('dashboard'));

    $response
        ->assertSessionHas(ResolveCurrentBusiness::SESSION_KEY, $membership->business_id)
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('workspace.business_id', $membership->business_id)
            ->where('workspace.role', 'cashier')
            ->where('workspace.abilities', ['scan', 'view_today_activity']));
});

test('users with several businesses are asked to choose', function () {
    $user = User::factory()->create();
    $zeta = Membership::factory()->for($user)->for(Business::factory()->state(['name' => 'Zeta']))->create();
    $alpha = Membership::factory()->for($user)->for(Business::factory()->state(['name' => 'Alpha']))->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('workspace.choose'));

    $this->get(route('workspace.choose'))->assertInertia(fn (Assert $page) => $page
        ->component('workspace/choose')
        ->where('options.0.business_id', $alpha->business_id)
        ->where('options.1.business_id', $zeta->business_id));
});

test('members can switch to another of their businesses', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create();
    $other = Membership::factory()->for($user)->manager()->create();

    $response = $this->actingAs($user)->put(route('workspace.update'), ['business_id' => $other->business_id]);

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(ResolveCurrentBusiness::SESSION_KEY, $other->business_id);
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('workspace.business_id', $other->business_id));
});

test('users cannot switch to a business they do not belong to', function () {
    $membership = Membership::factory()->create();
    $stranger = Business::factory()->create();

    $response = $this->actingAs($membership->user)
        ->withSession([ResolveCurrentBusiness::SESSION_KEY => $membership->business_id])
        ->put(route('workspace.update'), ['business_id' => $stranger->id]);

    $response->assertForbidden();
    expect(session(ResolveCurrentBusiness::SESSION_KEY))->toBe($membership->business_id);
});

test('suspended memberships cannot be opened', function () {
    $user = User::factory()->create();
    $active = Membership::factory()->for($user)->create();
    $suspended = Membership::factory()->for($user)->suspended()->create();

    $this->actingAs($user)
        ->put(route('workspace.update'), ['business_id' => $suspended->business_id])
        ->assertForbidden();

    $this->withSession([ResolveCurrentBusiness::SESSION_KEY => $suspended->business_id])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('workspace.business_id', $active->business_id));
});

test('users without a business are told there is nothing to open', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('workspace.none'));

    $this->get(route('workspace.none'))->assertInertia(fn (Assert $page) => $page->component('workspace/none'));
});

test('admins without a business are sent to the admin area', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertRedirect(route('admin.dashboard'));
});

test('only admins can open the admin area', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk();
});
