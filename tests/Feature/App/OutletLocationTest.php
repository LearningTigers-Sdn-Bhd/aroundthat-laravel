<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees and saves the location', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.location.edit', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/location')
            ->where('place.slug', $outlet->slug)
            ->where('can.update', true));

    $this->put(route('outlets.location.update', $outlet), ['latitude' => 95, 'longitude' => 116])
        ->assertSessionHasErrors('latitude');

    $this->put(route('outlets.location.update', $outlet), ['latitude' => 5.9804, 'longitude' => 116.0735])
        ->assertSessionHasNoErrors();

    expect((float) $outlet->refresh()->latitude)->toBe(5.9804);
});

test('managers cannot change the location', function () {
    $manager = Membership::factory()->manager()->create();
    $outlet = Outlet::factory()->for($manager->business)->create();

    $this->actingAs($manager->user)->get(route('outlets.location.edit', $outlet))->assertForbidden();
    $this->put(route('outlets.location.update', $outlet), ['latitude' => 5, 'longitude' => 116])->assertForbidden();
});
