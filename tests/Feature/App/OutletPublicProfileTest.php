<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Tag;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees the public page with only the tags their business can pick', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();
    $approved = Tag::factory()->create(['name' => 'Halal']);
    $own = Tag::factory()->pendingFrom($owner->business)->create(['name' => 'Rooftop']);
    Tag::factory()->pendingFrom()->create(['name' => 'Someone else’s']);

    $this->actingAs($owner->user)
        ->get(route('outlets.public.edit', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/public')
            ->where('place.slug', $outlet->slug)
            ->where('place.missing_for_listing', ['summary', 'category_id', 'coordinates'])
            ->has('tagOptions', 2)
            ->where('tagOptions.0', ['id' => $approved->id, 'name' => 'Halal'])
            ->where('tagOptions.1', ['id' => $own->id, 'name' => 'Rooftop'])
            ->where('can.update', true));
});

test('the owner saves the public page', function () {
    $owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->create();

    $this->actingAs($owner->user)
        ->put(route('outlets.public.update', $outlet), [
            'summary' => 'Kopi by the sea.',
            'tags' => ['Halal'],
            'whatsapp' => 'not a phone',
        ])
        ->assertSessionHasErrors('whatsapp');

    $this->put(route('outlets.public.update', $outlet), ['summary' => 'Kopi by the sea.', 'tags' => ['Halal']])
        ->assertSessionHasNoErrors();

    expect($outlet->refresh()->summary)->toBe('Kopi by the sea.');
    expect($outlet->tags()->pluck('name')->all())->toBe(['Halal']);
});

test('managers cannot change the public page', function () {
    $manager = Membership::factory()->manager()->create();
    $outlet = Outlet::factory()->for($manager->business)->create();

    $this->actingAs($manager->user)->get(route('outlets.public.edit', $outlet))->assertForbidden();
    $this->put(route('outlets.public.update', $outlet), ['summary' => 'Hi'])->assertForbidden();
});

test('an outlet of another business is not found', function () {
    $owner = Membership::factory()->owner()->create();

    $this->actingAs($owner->user)->get(route('outlets.public.edit', Outlet::factory()->create()))->assertNotFound();
});
