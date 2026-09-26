<?php

use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\Integration;
use App\Models\Outlet;
use App\Models\Tag;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(Integration::factory()->create());
});

test('categories list what owners can pick and retired ones public outlets still use, with public counts', function () {
    $cafe = Category::factory()->create(['name' => 'Café', 'position' => 1]);
    $retired = Category::factory()->create(['name' => 'Karaoke', 'position' => 2, 'is_active' => false]);
    Category::factory()->create(['name' => 'Unused retired', 'is_active' => false]);

    Outlet::factory()->publiclyVisible()->create(['category_id' => $cafe->id]);
    Outlet::factory()->publiclyVisible()->hidden()->create(['category_id' => $cafe->id]);
    Outlet::factory()->publiclyVisible()->create(['category_id' => $retired->id]);

    $this->getJson(route('api.v1.categories.index'))
        ->assertOk()
        ->assertExactJson(['data' => [
            ['slug' => 'cafe', 'name' => 'Café', 'outlet_count' => 1],
            ['slug' => 'karaoke', 'name' => 'Karaoke', 'outlet_count' => 1],
        ]]);
});

test('tags list only approved, unmerged tags', function () {
    Tag::factory()->create(['name' => 'Halal']);
    Tag::factory()->create(['name' => 'Waiting', 'status' => TagStatus::Pending]);
    Tag::factory()->create(['name' => 'Refused', 'status' => TagStatus::Rejected]);

    $this->getJson(route('api.v1.tags.index'))
        ->assertOk()
        ->assertExactJson(['data' => [['slug' => 'halal', 'name' => 'Halal', 'outlet_count' => 0]]]);
});

test('states list every Malaysian state with its public outlet count', function () {
    Outlet::factory()->publiclyVisible()->create(['state' => 'W.P. Kuala Lumpur']);
    Outlet::factory()->create(['state' => 'W.P. Kuala Lumpur']);

    $this->getJson(route('api.v1.states.index'))
        ->assertOk()
        ->assertJsonCount(16, 'data')
        ->assertJsonFragment(['slug' => 'wp-kuala-lumpur', 'name' => 'W.P. Kuala Lumpur', 'outlet_count' => 1]);
});
