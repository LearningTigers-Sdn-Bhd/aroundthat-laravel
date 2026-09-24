<?php

use App\Enums\MembershipRole;
use App\Http\Middleware\ResolveCurrentBusiness;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Outlet;
use App\Models\User;
use Database\Factories\InvitationFactory;
use Inertia\Testing\AssertableInertia as Assert;

test('the link shows the invitation to a guest', function () {
    $invitation = Invitation::factory()->cashier()->create(['email' => 'cashier@borneo.test']);
    $outlet = Outlet::factory()->for($invitation->business)->create(['name' => 'Gaya Street']);
    $invitation->outlets()->attach($outlet);

    $this->get(route('invitations.show', InvitationFactory::$lastToken))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/show')
            ->where('invitation.email', 'cashier@borneo.test')
            ->where('invitation.role', 'cashier')
            ->where('invitation.status', 'pending')
            ->where('invitation.outlet_names', ['Gaya Street'])
            ->where('hasAccount', false)
            ->where('signedInEmail', null));
});

test('an unknown link is not found', function () {
    $this->get(route('invitations.show', 'not-a-real-token'))->assertNotFound();
});

test('a guest without a login creates one, joins at the invited outlets and is logged in', function () {
    $business = Business::factory()->approved()->create();
    $outlet = Outlet::factory()->for($business)->approved()->create();
    $invitation = Invitation::factory()->for($business)->cashier()->withOutlets($outlet)->create(['email' => 'new@borneo.test']);

    $response = $this->post(route('invitations.accept', InvitationFactory::$lastToken), [
        'name' => 'Siti',
        'password' => 'a-strong-password-123',
        'password_confirmation' => 'a-strong-password-123',
    ]);

    $response->assertRedirect(route('dashboard'))->assertSessionHas(ResolveCurrentBusiness::SESSION_KEY, $business->id);
    $user = User::where('email', 'new@borneo.test')->sole();
    $this->assertAuthenticatedAs($user);
    expect($user->hasVerifiedEmail())->toBeTrue();
    $membership = $user->membershipFor($business);
    expect($membership->role)->toBe(MembershipRole::Cashier);
    expect($membership->outlets()->pluck('outlets.id')->all())->toBe([$outlet->id]);
    expect($invitation->refresh()->accepted_by_id)->toBe($user->id);
});

test('a guest without a login must choose a name and password', function () {
    Invitation::factory()->create();

    $this->post(route('invitations.accept', InvitationFactory::$lastToken), [])
        ->assertSessionHasErrors(['name', 'password']);

    $this->assertGuest();
    $this->assertDatabaseCount('memberships', 0);
});

test('someone who already has a login must log in first and is brought back to the invitation', function () {
    $user = User::factory()->create();
    Invitation::factory()->create(['email' => $user->email]);
    $token = InvitationFactory::$lastToken;

    $this->get(route('invitations.show', $token))
        ->assertInertia(fn (Assert $page) => $page->where('hasAccount', true))
        ->assertSessionHas('url.intended', route('invitations.show', $token));

    $this->post(route('invitations.accept', $token))
        ->assertSessionHasErrors(['invitation' => "This invitation is for {$user->email}. Log in with that email to accept it."]);
    expect($user->memberships()->count())->toBe(0);
});

test('the invited user joins and their email counts as verified', function () {
    $user = User::factory()->unverified()->create();
    $invitation = Invitation::factory()->owner()->create(['email' => $user->email]);

    $this->actingAs($user)->post(route('invitations.accept', InvitationFactory::$lastToken))
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    expect($user->membershipFor($invitation->business_id)->role)->toBe(MembershipRole::Owner);
});

test('a different logged-in user cannot accept it', function () {
    $invitation = Invitation::factory()->create(['email' => 'someone-else@borneo.test']);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->post(route('invitations.accept', InvitationFactory::$lastToken))
        ->assertSessionHasErrors('invitation');

    expect($stranger->membershipFor($invitation->business_id))->toBeNull();
    $this->assertDatabaseCount('users', 2);
});

test('a user with a temporary password can still open and accept the invitation', function () {
    $user = User::factory()->mustChangePassword()->create();
    Invitation::factory()->create(['email' => $user->email]);
    $token = InvitationFactory::$lastToken;

    $this->actingAs($user)->get(route('invitations.show', $token))->assertOk();
    $this->post(route('invitations.accept', $token))->assertRedirect(route('dashboard'));

    expect($user->memberships()->count())->toBe(1);
});

test('closed invitations cannot be accepted', function (string $state, string $message) {
    $user = User::factory()->create();
    Invitation::factory()->{$state}()->create(['email' => $user->email]);

    $this->actingAs($user)->post(route('invitations.accept', InvitationFactory::$lastToken))
        ->assertSessionHasErrors(['invitation' => $message]);

    expect($user->memberships()->count())->toBe(0);
})->with([
    'expired' => ['expired', 'This invitation has expired. Ask the business to send a new one.'],
    'accepted' => ['accepted', 'This invitation is no longer available.'],
    'cancelled' => ['cancelled', 'This invitation is no longer available.'],
]);

test('it cannot be accepted once an invited outlet stops trading', function () {
    $user = User::factory()->create();
    $business = Business::factory()->approved()->create();
    $outlet = Outlet::factory()->for($business)->approved()->archived()->create();
    Invitation::factory()->for($business)->manager()->withOutlets($outlet)->create(['email' => $user->email]);

    $this->actingAs($user)->post(route('invitations.accept', InvitationFactory::$lastToken))
        ->assertSessionHasErrors(['invitation' => 'An outlet in this invitation is no longer available. Ask the business to send a new one.']);

    expect($user->memberships()->count())->toBe(0);
});

test('it cannot be accepted while the business is suspended', function () {
    $user = User::factory()->create();
    Invitation::factory()->for(Business::factory()->approved()->suspended())->create(['email' => $user->email]);

    $this->actingAs($user)->post(route('invitations.accept', InvitationFactory::$lastToken))
        ->assertSessionHasErrors(['invitation' => 'This business is suspended.']);
});

test('the link holder can decline', function () {
    $invitation = Invitation::factory()->create();

    $this->post(route('invitations.decline', InvitationFactory::$lastToken))->assertRedirect(route('home'));

    expect($invitation->refresh()->declined_at)->not->toBeNull();
    $this->post(route('invitations.accept', InvitationFactory::$lastToken))->assertSessionHasErrors('invitation');
});
