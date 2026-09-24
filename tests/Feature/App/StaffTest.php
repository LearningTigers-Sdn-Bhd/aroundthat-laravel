<?php

use App\Enums\MembershipRole;
use App\Http\Middleware\ResolveCurrentBusiness;
use App\Jobs\SendStaffInvitation;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $this->business = $this->owner->business;
});

test('the staff page lists members, open invitations and the outlets staff can be given', function () {
    $outlet = Outlet::factory()->for($this->business)->approved()->create();
    Outlet::factory()->for($this->business)->create();
    Membership::factory()->cashier()->for($this->business)->withOutlets($outlet)->create();
    $invitation = Invitation::factory()->for($this->business)->create();
    Invitation::factory()->for($this->business)->cancelled()->create();

    $this->actingAs($this->owner->user)
        ->get(route('staff.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/staff/index')
            ->has('members', 2)
            ->has('invitations', 1)
            ->where('invitations.0.id', $invitation->id)
            ->where('outletOptions', [['id' => $outlet->id, 'name' => $outlet->name]])
            ->where('canInvite', true));
});

test('staff without the manage staff ability cannot open the staff page', function () {
    $cashier = Membership::factory()->cashier()->create();

    $this->actingAs($cashier->user)->get(route('staff.index'))->assertForbidden();
});

test('an owner invites a cashier to an outlet', function () {
    Queue::fake();
    $outlet = Outlet::factory()->for($this->business)->approved()->create();

    $this->actingAs($this->owner->user)
        ->post(route('staff.invitations.store'), ['email' => 'Siti@Example.test', 'role' => 'cashier', 'outlet_ids' => [$outlet->id]])
        ->assertSessionHasNoErrors();

    $invitation = $this->business->invitations()->sole();
    expect($invitation)
        ->email->toBe('siti@example.test')
        ->role->toBe(MembershipRole::Cashier);
    expect($invitation->outlets->modelKeys())->toBe([$outlet->id]);
    Queue::assertPushed(SendStaffInvitation::class);
});

test('a cashier cannot be invited without an outlet', function () {
    $this->actingAs($this->owner->user)
        ->post(route('staff.invitations.store'), ['email' => 'siti@example.test', 'role' => 'cashier'])
        ->assertSessionHasErrors(['outlet_ids' => 'Select at least one approved, active outlet of this business.']);

    expect($this->business->invitations()->exists())->toBeFalse();
});

test('an owner resends and cancels an invitation', function () {
    Queue::fake();
    $invitation = Invitation::factory()->for($this->business)->create();

    $this->actingAs($this->owner->user)->post(route('staff.invitations.resend', $invitation))->assertSessionHasNoErrors();
    Queue::assertPushed(SendStaffInvitation::class);

    $this->delete(route('staff.invitations.destroy', $invitation))->assertSessionHasNoErrors();
    expect($invitation->refresh()->cancelled_at)->not->toBeNull();
});

test('an owner changes a member\'s role and outlets', function () {
    $outlet = Outlet::factory()->for($this->business)->approved()->create();
    $cashier = Membership::factory()->cashier()->for($this->business)->create();

    $this->actingAs($this->owner->user)
        ->put(route('staff.update', $cashier), ['role' => 'manager', 'outlet_ids' => [$outlet->id]])
        ->assertSessionHasNoErrors();

    expect($cashier->refresh()->role)->toBe(MembershipRole::Manager);
    expect($cashier->outlets->modelKeys())->toBe([$outlet->id]);
});

test('an owner suspends, reactivates and removes a member', function () {
    $cashier = Membership::factory()->cashier()->for($this->business)->create();

    $this->actingAs($this->owner->user)
        ->post(route('staff.suspend', $cashier), ['reason' => 'On leave.'])
        ->assertSessionHasNoErrors();
    expect($cashier->refresh()->suspension_reason)->toBe('On leave.');

    $this->post(route('staff.reactivate', $cashier))->assertSessionHasNoErrors();
    expect($cashier->refresh()->isActive())->toBeTrue();

    $this->delete(route('staff.destroy', $cashier))->assertSessionHasNoErrors();
    expect(Membership::find($cashier->id))->toBeNull();
});

test('suspending a member needs a reason', function () {
    $cashier = Membership::factory()->cashier()->for($this->business)->create();

    $this->actingAs($this->owner->user)->post(route('staff.suspend', $cashier))->assertSessionHasErrors('reason');

    expect($cashier->refresh()->isActive())->toBeTrue();
});

test('an owner cannot change their own membership', function () {
    $this->actingAs($this->owner->user)
        ->put(route('staff.update', $this->owner), ['role' => 'cashier'])
        ->assertForbidden();

    expect($this->owner->refresh()->role)->toBe(MembershipRole::Owner);
});

test('members and invitations of another business the user owns are not found from this one', function (string $method, Closure $route) {
    $otherOwner = Membership::factory()->owner()->for($this->owner->user)->create();
    $otherCashier = Membership::factory()->cashier()->for($otherOwner->business)->create();
    $otherInvitation = Invitation::factory()->for($otherOwner->business)->create();

    $this->actingAs($this->owner->user)
        ->withSession([ResolveCurrentBusiness::SESSION_KEY => $this->business->id])
        ->call($method, $route($otherCashier, $otherInvitation), ['reason' => 'Test.'])
        ->assertNotFound();

    expect($otherCashier->refresh()->isActive())->toBeTrue();
    expect($otherInvitation->refresh()->isOpen())->toBeTrue();
})->with([
    'suspend member' => ['POST', fn (Membership $member) => route('staff.suspend', $member)],
    'remove member' => ['DELETE', fn (Membership $member) => route('staff.destroy', $member)],
    'cancel invitation' => ['DELETE', fn (Membership $member, Invitation $invitation) => route('staff.invitations.destroy', $invitation)],
]);
