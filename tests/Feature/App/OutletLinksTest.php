<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees and saves the social links', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();

    $this->actingAs($owner->user)
        ->get(route('outlets.links.edit', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/links')
            ->where('place.slug', $outlet->slug)
            ->where('can.update', true));

    $this->put(route('outlets.links.update', $outlet), ['whatsapp' => 'not a phone', 'website' => 'ftp://kopi.test'])
        ->assertSessionHasErrors(['whatsapp', 'website']);

    $this->put(route('outlets.links.update', $outlet), ['whatsapp' => '+60 12-345 6789', 'website' => 'https://kopi.test'])
        ->assertSessionHasNoErrors();

    expect($outlet->refresh()->only('whatsapp', 'website'))->toBe(['whatsapp' => '+60 12-345 6789', 'website' => 'https://kopi.test']);
});

test('managers cannot change the social links', function () {
    $manager = Membership::factory()->manager()->create();
    $outlet = Outlet::factory()->for($manager->business)->create();

    $this->actingAs($manager->user)->get(route('outlets.links.edit', $outlet))->assertForbidden();
    $this->put(route('outlets.links.update', $outlet), ['website' => 'https://kopi.test'])->assertForbidden();
});
