<?php

use App\Enums\OnboardingStatus;
use App\Jobs\SendStaffInvitation;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('only admins can open the admin area', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.businesses.index'))->assertForbidden();

    auth()->logout();
    $this->get(route('admin.businesses.index'))->assertRedirect(route('login'));
});

test('businesses can be filtered by status, suspension and search', function (array $filter, string $expected) {
    Business::factory()->pending()->create(['name' => 'Pending Cafe']);
    Business::factory()->approved()->suspended()->create(['name' => 'Suspended Diner']);
    Business::factory()->approved()->create(['name' => 'Approved Bistro', 'registration_number' => 'SSM-2026-01']);

    $this->actingAs($this->admin)
        ->get(route('admin.businesses.index', ['filter' => $filter]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/businesses/index')
            ->has('businesses.data', 1)
            ->where('businesses.data.0.name', $expected)
            ->where('businesses.meta.total', 1));
})->with([
    'onboarding status' => [['onboarding_status' => 'pending'], 'Pending Cafe'],
    'suspended' => [['suspended' => 'true'], 'Suspended Diner'],
    'search' => [['search' => 'ssm-2026-01', 'onboarding_status' => 'approved', 'suspended' => 'false'], 'Approved Bistro'],
]);

test('businesses can be sorted by name', function () {
    Business::factory()->create(['name' => 'Zeta']);
    Business::factory()->create(['name' => 'Alpha']);

    $this->actingAs($this->admin)
        ->get(route('admin.businesses.index', ['sort' => '-name']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('businesses.data.0.name', 'Zeta')
            ->where('businesses.data.1.name', 'Alpha'));
});

test('the new business form opens as a modal over the business list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.businesses.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/businesses/index')
            ->where('_inertiaui_modal.component', 'admin/businesses/create')
            ->has('_inertiaui_modal.props.ownerMethods'));
});

test('an admin onboards a business by inviting its owner', function () {
    Queue::fake([SendStaffInvitation::class]);

    $response = $this->actingAs($this->admin)->post(route('admin.businesses.store'), [
        'business' => ['name' => 'Borneo Restaurant', 'contact_email' => 'hello@borneo.test', 'timezone' => 'Asia/Kuala_Lumpur'],
        'owner_method' => 'invite',
        'owner_email' => 'owner@borneo.test',
        'approve_immediately' => '1',
    ]);

    $business = Business::where('name', 'Borneo Restaurant')->sole();
    $response->assertRedirect(route('admin.businesses.show', $business));
    expect($business->onboarding_status)->toBe(OnboardingStatus::Approved);
    expect($business->invitations()->sole()->email)->toBe('owner@borneo.test');
});

test('onboarding reports missing business details under their field names', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.businesses.store'), ['owner_method' => 'invite', 'owner_email' => 'owner@borneo.test'])
        ->assertSessionHasErrors(['business.name', 'business.contact_email']);

    $this->assertDatabaseCount('businesses', 0);
});

test('a business page shows its outlets, members, open invitations and only its own history', function () {
    $business = Business::factory()->approved()->create();
    $outlet = Outlet::factory()->for($business)->approved()->create();
    Membership::factory()->owner()->for($business)->create();
    Invitation::factory()->for($business)->cashier()->create();
    Invitation::factory()->for($business)->accepted()->create();
    $outlet->update(['name' => 'Renamed Outlet']);
    Business::factory()->create()->update(['name' => 'Someone else']);

    $this->actingAs($this->admin)
        ->get(route('admin.businesses.show', $business))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/businesses/show')
            ->where('business.id', $business->id)
            ->has('outlets', 1)
            ->has('members', 1)
            ->has('invitations', 1)
            ->missing('activities')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('activities.0.event', 'updated')
                ->where('activities.0.subject_type', 'outlet')
                ->where('activities', fn ($activities) => collect($activities)->every(
                    fn (array $activity) => $activity['subject_type'] !== 'business' || $activity['subject_id'] === $business->id,
                ))));
});

test('an admin approves a pending business', function () {
    $business = Business::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.businesses.show', $business))
        ->post(route('admin.businesses.approve', $business))
        ->assertRedirect(route('admin.businesses.show', $business));

    expect($business->refresh()->onboarding_status)->toBe(OnboardingStatus::Approved);
    expect($business->approved_by_id)->toBe($this->admin->id);
});

test('approving a business that is not waiting for review shows why', function () {
    $business = Business::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.businesses.approve', $business))
        ->assertSessionHasErrors(['business' => 'Only a business waiting for review can be approved or rejected.']);
});

test('rejecting and suspending need a reason', function (string $route) {
    $business = Business::factory()->pending()->create();

    $this->actingAs($this->admin)->post(route($route, $business), ['reason' => ''])->assertSessionHasErrors('reason');

    expect($business->refresh()->onboarding_status)->toBe(OnboardingStatus::Pending);
    expect($business->suspended_at)->toBeNull();
})->with(['admin.businesses.reject', 'admin.businesses.suspend']);

test('an admin rejects with a reason the owner can read', function () {
    $business = Business::factory()->pending()->create();

    $this->actingAs($this->admin)->post(route('admin.businesses.reject', $business), ['reason' => 'Registration number is missing.']);

    expect($business->refresh()->onboarding_status)->toBe(OnboardingStatus::Rejected);
    expect($business->rejection_reason)->toBe('Registration number is missing.');
});

test('an admin suspends and reactivates a business', function () {
    $business = Business::factory()->approved()->create();

    $this->actingAs($this->admin)->post(route('admin.businesses.suspend', $business), ['reason' => 'Unpaid fees.']);
    expect($business->refresh()->suspension_reason)->toBe('Unpaid fees.');

    $this->post(route('admin.businesses.reactivate', $business));
    expect($business->refresh()->suspended_at)->toBeNull();
});

test('an admin resends and cancels an owner invitation', function () {
    Queue::fake([SendStaffInvitation::class]);
    $invitation = Invitation::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.invitations.resend', $invitation))->assertSessionHasNoErrors();
    Queue::assertPushed(SendStaffInvitation::class);

    $this->delete(route('admin.invitations.destroy', $invitation))->assertSessionHasNoErrors();
    expect($invitation->refresh()->cancelled_at)->not->toBeNull();
});
