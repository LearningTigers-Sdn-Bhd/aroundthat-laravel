<?php

use App\Enums\OnboardingStatus;
use App\Http\Middleware\ResolveCurrentBusiness;
use App\Models\Business;
use App\Models\Membership;
use Inertia\Testing\AssertableInertia as Assert;

function businessDetailsInput(array $overrides = []): array
{
    return [
        'name' => 'Gaya Coffee',
        'registered_name' => 'Gaya Coffee Sdn Bhd',
        'registration_number' => '202601000123',
        'contact_email' => 'hello@gaya.test',
        'timezone' => 'Asia/Kuala_Lumpur',
        ...$overrides,
    ];
}

test('an owner opens their business details and can submit a draft', function () {
    $owner = Membership::factory()->owner()->create();

    $this->actingAs($owner->user)
        ->get(route('business.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/business/edit')
            ->where('business.id', $owner->business_id)
            ->where('can', ['update' => true, 'submit' => true, 'updatePublicProfile' => true]));
});

test('staff without the manage business ability cannot open the business details', function () {
    $manager = Membership::factory()->manager()->create();

    $this->actingAs($manager->user)->get(route('business.edit'))->assertForbidden();
});

test('the business page shows the business being worked in, not another one the user owns', function () {
    $owner = Membership::factory()->owner()->create();
    $cashier = Membership::factory()->cashier()->for($owner->user)->create();

    $this->actingAs($owner->user)
        ->withSession([ResolveCurrentBusiness::SESSION_KEY => $cashier->business_id])
        ->get(route('business.edit'))
        ->assertForbidden();
});

test('an owner saves business details', function () {
    $owner = Membership::factory()->owner()->create();

    $this->actingAs($owner->user)
        ->put(route('business.update'), businessDetailsInput())
        ->assertSessionHasNoErrors();

    expect($owner->business->refresh())
        ->name->toBe('Gaya Coffee')
        ->registration_number->toBe('202601000123');
});

test('business details cannot be changed while waiting for review', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->pending())->create();

    $this->actingAs($owner->user)->put(route('business.update'), businessDetailsInput())->assertForbidden();

    expect($owner->business->refresh()->name)->not->toBe('Gaya Coffee');
});

test('an owner submits a complete business for review', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->state(businessDetailsInput()))->create();

    $this->actingAs($owner->user)->post(route('business.submit'))->assertSessionHasNoErrors();

    expect($owner->business->refresh()->onboarding_status)->toBe(OnboardingStatus::Pending);
});

test('submitting a business names the details it still needs', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->state(['registered_name' => null]))->create();

    $this->actingAs($owner->user)
        ->post(route('business.submit'))
        ->assertSessionHasErrors(['registered_name' => 'Complete this before submitting for review.']);

    expect($owner->business->refresh()->onboarding_status)->toBe(OnboardingStatus::Draft);
});
