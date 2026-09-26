<?php

use App\Actions\Tags\SyncOutletTags;
use App\Enums\ImageKind;
use App\Enums\TagStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\Image;
use App\Models\Integration;
use App\Models\Outlet;
use App\Models\Tag;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(Integration::factory()->create());
});

test('the list shows only public outlets', function () {
    $public = Outlet::factory()->publiclyVisible()->create(['name' => 'Kopi Corner']);
    Outlet::factory()->publiclyVisible()->hidden()->create();
    Outlet::factory()->publiclyVisible()->create(['is_listed' => false]);
    Outlet::factory()->publiclyVisible()->create(['summary' => null]);
    Outlet::factory()->for(Business::factory())->approved()->listed()->create();
    Outlet::factory()->publiclyVisible()->archived()->create();

    $this->getJson(route('api.v1.outlets.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $public->slug)
        ->assertJsonPath('data.0.name', 'Kopi Corner')
        ->assertJsonPath('data.0.business.slug', $public->business->slug)
        ->assertJsonPath('data.0.address.state_slug', 'sabah')
        ->assertJsonStructure(['data' => [['category' => ['slug', 'name'], 'location' => ['latitude', 'longitude'], 'open_now' => ['is_open', 'closes_at', 'next_opens_at'], 'updated_at']], 'meta' => ['next_cursor']]);
});

test('an outlet shows only its approved tags and its cover photo', function () {
    $outlet = Outlet::factory()->publiclyVisible()->create();
    $outlet->tags()->attach([
        Tag::factory()->create(['name' => 'Halal'])->id,
        Tag::factory()->create(['name' => 'Waiting', 'status' => TagStatus::Pending])->id,
    ]);
    Image::factory()->for($outlet, 'imageable')->create(['kind' => ImageKind::Cover, 'alt_text' => 'The front']);
    Image::factory()->for($outlet, 'imageable')->removed()->create(['kind' => ImageKind::Cover]);

    $this->getJson(route('api.v1.outlets.index'))
        ->assertJsonPath('data.0.tags', [['slug' => 'halal', 'name' => 'Halal']])
        ->assertJsonPath('data.0.cover_image.alt_text', 'The front');
});

test('outlets can be filtered by category, every tag, state and search', function (Closure $filter, string $expected) {
    $cafe = Category::factory()->create(['name' => 'Cafe']);
    $halal = Tag::factory()->create(['name' => 'Halal']);
    $wifi = Tag::factory()->create(['name' => 'Wifi']);

    Outlet::factory()->publiclyVisible()->create(['name' => 'Kopi Corner', 'category_id' => $cafe->id, 'state' => 'Selangor'])
        ->tags()->attach([$halal->id, $wifi->id]);
    Outlet::factory()->publiclyVisible()->create(['name' => 'Roti House', 'state' => 'Sabah'])
        ->tags()->attach([$halal->id]);

    $this->getJson(route('api.v1.outlets.index', ['filter' => $filter()]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', $expected);
})->with([
    'category' => [fn () => ['category' => 'cafe'], 'Kopi Corner'],
    'every tag' => [fn () => ['tag' => 'halal,wifi'], 'Kopi Corner'],
    'state' => [fn () => ['state' => 'sabah'], 'Roti House'],
    'search' => [fn () => ['search' => 'roti'], 'Roti House'],
]);

test('an unknown filter value is refused instead of returning nothing', function () {
    Tag::factory()->create(['name' => 'Waiting', 'status' => TagStatus::Pending]);

    $this->getJson(route('api.v1.outlets.index', ['filter' => ['category' => 'nope', 'tag' => 'waiting']]))
        ->assertUnprocessable()
        ->assertExactJson(['errors' => [
            ['path' => '/filter/category/0', 'code' => 'invalid', 'message' => 'Unknown category. See /api/v1/categories.'],
            ['path' => '/filter/tag/0', 'code' => 'invalid', 'message' => 'Unknown tag. See /api/v1/tags.'],
        ]]);
});

test('updated_since needs a timezone', function () {
    $this->getJson(route('api.v1.outlets.index', ['filter' => ['updated_since' => '2026-09-01 00:00']]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.path', '/filter/updated_since');
});

test('the list pages with a cursor', function () {
    Outlet::factory()->publiclyVisible()->create(['name' => 'Alpha']);
    Outlet::factory()->publiclyVisible()->create(['name' => 'Bravo']);
    Outlet::factory()->publiclyVisible()->create(['name' => 'Charlie']);

    $first = $this->getJson(route('api.v1.outlets.index', ['per_page' => 2]))
        ->assertJsonPath('data.*.name', ['Alpha', 'Bravo']);

    $this->getJson(route('api.v1.outlets.index', ['per_page' => 2, 'cursor' => $first->json('meta.next_cursor')]))
        ->assertJsonPath('data.*.name', ['Charlie'])
        ->assertJsonPath('meta.next_cursor', null);
});

test('a sync with updated_since picks up an outlet whose tags changed', function () {
    $this->travelTo(now()->subDay());
    $outlet = Outlet::factory()->publiclyVisible()->create();
    Outlet::factory()->publiclyVisible()->create();
    $this->travelBack();

    $since = now()->subMinute()->toIso8601String();
    app(SyncOutletTags::class)->handle($outlet, [Tag::factory()->create()->id]);

    $this->getJson(route('api.v1.outlets.index', ['filter' => ['updated_since' => $since], 'sort' => 'updated_at']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $outlet->slug);
});

test('an outlet shows its hours, upcoming special dates, photos and business', function () {
    $this->travelTo(now()->setTimezone('Asia/Kuala_Lumpur')->setTime(12, 0));
    $outlet = Outlet::factory()->publiclyVisible()->create(['description' => 'Since 1978.']);
    $outlet->dateExceptions()->create(['date' => now('Asia/Kuala_Lumpur')->subDays(2)->toDateString(), 'is_closed' => true]);
    $outlet->dateExceptions()->create(['date' => now('Asia/Kuala_Lumpur')->addDays(3)->toDateString(), 'is_closed' => true, 'note' => 'Hari Raya']);
    Image::factory()->for($outlet, 'imageable')->create(['kind' => ImageKind::Gallery, 'position' => 1]);
    Image::factory()->for($outlet, 'imageable')->create(['kind' => ImageKind::Cover]);
    Image::factory()->for($outlet->business, 'imageable')->create(['kind' => ImageKind::Logo, 'alt_text' => 'Logo']);

    $this->getJson(route('api.v1.outlets.show', $outlet->slug))
        ->assertOk()
        ->assertJsonPath('data.description', 'Since 1978.')
        ->assertJsonPath('data.regular_hours.1', [['opens' => '09:00', 'closes' => '22:00']])
        ->assertJsonPath('data.open_now.is_open', true)
        ->assertJsonCount(1, 'data.date_exceptions')
        ->assertJsonPath('data.date_exceptions.0.note', 'Hari Raya')
        ->assertJsonPath('data.images.*.kind', ['cover', 'gallery'])
        ->assertJsonPath('data.business.logo.alt_text', 'Logo');
});

test('an outlet that is not public is not found', function () {
    $outlet = Outlet::factory()->publiclyVisible()->hidden()->create();

    $this->getJson(route('api.v1.outlets.show', $outlet->slug))
        ->assertNotFound()
        ->assertExactJson(['errors' => [['path' => '', 'code' => 'not_found', 'message' => 'Not found.']]]);
});
