<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Membership;
use App\Models\Outlet;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees the listing on the details tab and lists the outlet', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.edit', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/edit')
            ->where('place.is_listed', false)
            ->where('place.missing_for_listing', ['summary', 'category_id', 'coordinates', 'hours'])
            ->where('can.updateListing', true));

    $this->put(route('outlets.listing.update', $outlet), ['is_listed' => '1'])
        ->assertSessionHasErrors('is_listed');

    $outlet->forceFill([
        'summary' => 'Kopi by the sea.',
        'category_id' => Category::factory()->create()->id,
        'latitude' => 5.9804,
        'longitude' => 116.0735,
        'regular_hours' => ['1' => [['opens' => '09:00', 'closes' => '17:00']]],
    ])->saveQuietly();

    $this->put(route('outlets.listing.update', $outlet), ['is_listed' => '1'])->assertSessionHasNoErrors();

    expect($outlet->refresh()->isPublic())->toBeTrue();
});

test('managers cannot change the listing', function () {
    $manager = Membership::factory()->manager()->create();
    $outlet = Outlet::factory()->for($manager->business)->create();

    $this->actingAs($manager->user)->put(route('outlets.listing.update', $outlet), ['is_listed' => '1'])->assertForbidden();
});
