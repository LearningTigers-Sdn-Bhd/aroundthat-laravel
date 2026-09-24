<?php

use App\Enums\OnboardingStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function outletInput(array $overrides = []): array
{
    return [
        'name' => 'Gaya Street',
        'address_line_1' => '12 Jalan Gaya',
        'city' => 'Kota Kinabalu',
        'state' => 'Sabah',
        'postcode' => '88000',
        ...$overrides,
    ];
}

test('the new outlet form opens as a modal over its business', function () {
    $business = Business::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.businesses.outlets.create', $business))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/businesses/show')
            ->where('business.id', $business->id)
            ->where('_inertiaui_modal.component', 'admin/outlets/create')
            ->where('_inertiaui_modal.props.business.id', $business->id));
});

test('an admin adds a draft outlet to a business', function () {
    $business = Business::factory()->create();

    $response = $this->actingAs($this->admin)->post(route('admin.businesses.outlets.store', $business), outletInput());

    $outlet = $business->outlets()->sole();
    $response->assertRedirect(route('admin.outlets.show', $outlet));
    expect($outlet->onboarding_status)->toBe(OnboardingStatus::Draft);
    expect($outlet->address_line_1)->toBe('12 Jalan Gaya');
});

test('an admin can approve a new outlet at once only for an approved business', function (Closure $makeBusiness, ?OnboardingStatus $expected) {
    $business = $makeBusiness();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.businesses.outlets.store', $business), outletInput(['approve_immediately' => '1']));

    expect($business->outlets()->first()?->onboarding_status)->toBe($expected);
    if ($expected === null) {
        $response->assertSessionHasErrors(['approve_immediately' => 'Approve the business before approving its outlets.']);
    }
})->with([
    'approved business' => [fn () => Business::factory()->approved()->create(), OnboardingStatus::Approved],
    'draft business' => [fn () => Business::factory()->create(), null],
]);

test('an outlet needs an address', function () {
    $business = Business::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.businesses.outlets.store', $business), ['name' => 'Gaya Street'])
        ->assertSessionHasErrors(['address_line_1', 'city', 'state', 'postcode']);
});

test('the outlet page offers only approved, active outlets other than itself as hosts', function () {
    $outlet = Outlet::factory()->approved()->create();
    $mall = Outlet::factory()->approved()->create(['name' => 'Imago Mall']);
    Outlet::factory()->create(['name' => 'Draft Mall']);
    Outlet::factory()->approved()->archived()->create(['name' => 'Closed Mall']);

    $this->actingAs($this->admin)
        ->get(route('admin.outlets.show', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/outlets/show')
            ->where('outlet.id', $outlet->id)
            ->where('hostCandidates', [['id' => $mall->id, 'name' => 'Imago Mall', 'business_name' => $mall->business->name]])
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('activities.0.event', 'created')));
});

test('an admin reviews, suspends and archives an outlet', function (string $route, Closure $makeOutlet, array $input, Closure $check) {
    $outlet = $makeOutlet();

    $this->actingAs($this->admin)->post(route($route, $outlet), $input)->assertSessionHasNoErrors();

    expect($check($outlet->refresh()))->toBeTrue();
})->with([
    'approve' => ['admin.outlets.approve', fn () => Outlet::factory()->for(Business::factory()->approved())->pending()->create(), [], fn (Outlet $outlet) => $outlet->isApproved()],
    'reject' => ['admin.outlets.reject', fn () => Outlet::factory()->pending()->create(), ['reason' => 'Wrong address.'], fn (Outlet $outlet) => $outlet->rejection_reason === 'Wrong address.'],
    'suspend' => ['admin.outlets.suspend', fn () => Outlet::factory()->approved()->create(), ['reason' => 'Complaints.'], fn (Outlet $outlet) => $outlet->suspension_reason === 'Complaints.'],
    'reactivate' => ['admin.outlets.reactivate', fn () => Outlet::factory()->approved()->suspended()->create(), [], fn (Outlet $outlet) => ! $outlet->isSuspended()],
    'archive' => ['admin.outlets.archive', fn () => Outlet::factory()->approved()->create(), [], fn (Outlet $outlet) => $outlet->isArchived()],
    'restore' => ['admin.outlets.restore', fn () => Outlet::factory()->approved()->archived()->create(), [], fn (Outlet $outlet) => ! $outlet->isArchived()],
]);

test('approving an outlet before its business shows why', function () {
    $outlet = Outlet::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.outlets.approve', $outlet))
        ->assertSessionHasErrors(['outlet' => 'Approve the business before approving its outlets.']);
});

test('suspending an outlet needs a reason', function () {
    $outlet = Outlet::factory()->approved()->create();

    $this->actingAs($this->admin)->post(route('admin.outlets.suspend', $outlet))->assertSessionHasErrors('reason');

    expect($outlet->refresh()->isSuspended())->toBeFalse();
});

test('an admin sets and clears the outlet an outlet sits inside', function () {
    $outlet = Outlet::factory()->approved()->create();
    $mall = Outlet::factory()->approved()->create();

    $this->actingAs($this->admin)->put(route('admin.outlets.host.update', $outlet), ['host_outlet_id' => $mall->id])->assertSessionHasNoErrors();
    expect($outlet->refresh()->host_outlet_id)->toBe($mall->id);

    $this->delete(route('admin.outlets.host.destroy', $outlet))->assertSessionHasNoErrors();
    expect($outlet->refresh()->host_outlet_id)->toBeNull();
});

test('a host that would place an outlet inside itself is refused', function () {
    $mall = Outlet::factory()->approved()->create();
    $shop = Outlet::factory()->approved()->create(['host_outlet_id' => $mall->id]);

    $this->actingAs($this->admin)
        ->put(route('admin.outlets.host.update', $mall), ['host_outlet_id' => $shop->id])
        ->assertSessionHasErrors(['host_outlet_id' => 'This host would place the outlet inside itself.']);
});

test('an admin hides an outlet with a reason and unhides it', function () {
    $outlet = Outlet::factory()->approved()->listed()->create();
    $this->actingAs($this->admin);

    $this->post(route('admin.outlets.hide', $outlet))->assertSessionHasErrors('reason');
    $this->post(route('admin.outlets.hide', $outlet), ['reason' => 'Misleading photos.'])->assertSessionHasNoErrors();
    expect($outlet->refresh()->hidden_reason)->toBe('Misleading photos.');

    $this->post(route('admin.outlets.unhide', $outlet))->assertSessionHasNoErrors();
    expect($outlet->refresh()->hidden_at)->toBeNull();
});

test('only admins hide outlets', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.outlets.hide', Outlet::factory()->approved()->create()), ['reason' => 'No.'])
        ->assertForbidden();
});
