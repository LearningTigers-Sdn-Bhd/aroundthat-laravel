<?php

use App\Enums\OnboardingStatus;
use App\Http\Middleware\ResolveCurrentBusiness;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use Inertia\Testing\AssertableInertia as Assert;

function ownerOutletInput(array $overrides = []): array
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

test('the outlet list shows only the outlets of the business being worked in', function () {
    $owner = Membership::factory()->owner()->create();
    $outlet = Outlet::factory()->for($owner->business)->create();
    Outlet::factory()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/index')
            ->has('outlets', 1)
            ->where('outlets.0.id', $outlet->id)
            ->where('canCreate', true));
});

test('staff without the manage outlets ability cannot open outlets', function () {
    $manager = Membership::factory()->manager()->create();

    $this->actingAs($manager->user)->get(route('outlets.index'))->assertForbidden();
    $this->post(route('outlets.store'), ownerOutletInput())->assertForbidden();
});

test('the new outlet form opens as a modal over the outlet list', function () {
    $owner = Membership::factory()->owner()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/index')
            ->where('_inertiaui_modal.component', 'app/outlets/create')
            ->where('_inertiaui_modal.props.timezone', $owner->business->timezone));
});

test('an owner adds a draft outlet', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();

    $response = $this->actingAs($owner->user)->post(route('outlets.store'), ownerOutletInput());

    $outlet = $owner->business->outlets()->sole();
    $response->assertRedirect(route('outlets.edit', $outlet));
    expect($outlet->onboarding_status)->toBe(OnboardingStatus::Draft);
});

test('an owner cannot approve their own outlet while adding it', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();

    $this->actingAs($owner->user)->post(route('outlets.store'), ownerOutletInput(['approve_immediately' => '1']));

    expect($owner->business->outlets()->sole()->onboarding_status)->toBe(OnboardingStatus::Draft);
});

test('an owner saves outlet details', function () {
    $owner = Membership::factory()->owner()->create();
    $outlet = Outlet::factory()->for($owner->business)->create();

    $this->actingAs($owner->user)
        ->put(route('outlets.update', $outlet), ownerOutletInput(['name' => 'Gaya Corner']))
        ->assertSessionHasNoErrors();

    expect($outlet->refresh()->name)->toBe('Gaya Corner');
});

test('an outlet waiting for review opens read-only', function () {
    $owner = Membership::factory()->owner()->create();
    $outlet = Outlet::factory()->for($owner->business)->pending()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.edit', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/edit')
            ->where('can', ['update' => false, 'submit' => false, 'archive' => true]));

    $this->put(route('outlets.update', $outlet), ownerOutletInput())->assertForbidden();
});

test('an owner submits an outlet once the business is approved', function (Closure $makeBusiness, bool $submitted) {
    $owner = Membership::factory()->owner()->for($makeBusiness())->create();
    $outlet = Outlet::factory()->for($owner->business)->create();

    $response = $this->actingAs($owner->user)->post(route('outlets.submit', $outlet));

    $submitted ? $response->assertSessionHasNoErrors() : $response->assertForbidden();
    expect($outlet->refresh()->onboarding_status)->toBe($submitted ? OnboardingStatus::Pending : OnboardingStatus::Draft);
})->with([
    'approved business' => [fn () => Business::factory()->approved(), true],
    'draft business' => [fn () => Business::factory(), false],
]);

test('an owner archives and restores an outlet', function () {
    $owner = Membership::factory()->owner()->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();

    $this->actingAs($owner->user)->post(route('outlets.archive', $outlet))->assertSessionHasNoErrors();
    expect($outlet->refresh()->isArchived())->toBeTrue();

    $this->post(route('outlets.restore', $outlet))->assertSessionHasNoErrors();
    expect($outlet->refresh()->isArchived())->toBeFalse();
});

test('an outlet of another business the user owns is not found from this one', function (string $method, string $route) {
    $owner = Membership::factory()->owner()->create();
    $otherOwner = Membership::factory()->owner()->for($owner->user)->create();
    $otherOutlet = Outlet::factory()->for($otherOwner->business)->create();

    $this->actingAs($owner->user)
        ->withSession([ResolveCurrentBusiness::SESSION_KEY => $owner->business_id])
        ->call($method, route($route, $otherOutlet), ownerOutletInput())
        ->assertNotFound();

    expect($otherOutlet->refresh())
        ->name->not->toBe('Gaya Street')
        ->onboarding_status->toBe(OnboardingStatus::Draft);
})->with([
    'edit' => ['GET', 'outlets.edit'],
    'update' => ['PUT', 'outlets.update'],
    'submit' => ['POST', 'outlets.submit'],
    'archive' => ['POST', 'outlets.archive'],
]);
