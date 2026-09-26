<?php

use App\Enums\TagStatus;
use App\Models\Outlet;
use App\Models\Tag;
use Inertia\Testing\AssertableInertia as Assert;

test('anyone can open a public outlet page, which shows only approved tags', function () {
    $outlet = Outlet::factory()->publiclyVisible()->create(['name' => 'Kopi Corner']);
    $outlet->tags()->attach([
        Tag::factory()->create(['name' => 'Halal'])->id,
        Tag::factory()->create(['name' => 'Waiting', 'status' => TagStatus::Pending])->id,
    ]);

    $this->get(route('places.show', $outlet->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('places/show')
            ->where('place.name', 'Kopi Corner')
            ->has('place.place.tags', 1)
            ->where('place.place.tags.0.name', 'Halal'));
});

test('an outlet visitors cannot see has no public page', function (Closure $makeOutlet) {
    $this->get(route('places.show', $makeOutlet()->slug))->assertNotFound();
})->with([
    'hidden' => [fn () => Outlet::factory()->publiclyVisible()->hidden()->create()],
    'not listed' => [fn () => Outlet::factory()->publiclyVisible()->create(['is_listed' => false])],
    'not approved' => [fn () => Outlet::factory()->listed()->create()],
]);
