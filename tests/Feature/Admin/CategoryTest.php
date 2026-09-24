<?php

use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('an admin adds a category at the end of the list', function () {
    Category::factory()->create(['position' => 4]);

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'Hawker & food court', 'is_active' => '1'])
        ->assertSessionHasNoErrors()->assertRedirect();

    $category = Category::where('slug', 'hawker-food-court')->sole();
    expect($category->position)->toBe(5);
    expect($category->is_active)->toBeTrue();
});

test('a second category with the same slug is refused', function () {
    Category::factory()->create(['name' => 'Café']);

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'cafe'])
        ->assertSessionHasErrors(['name' => 'A category with this name already exists.']);
});

test('renaming and hiding a category keeps its slug', function () {
    $category = Category::factory()->create(['name' => 'Café']);

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $category), ['name' => 'Coffee shop'])
        ->assertSessionHasNoErrors();

    $category->refresh();
    expect($category->name)->toBe('Coffee shop');
    expect($category->slug)->toBe('cafe');
    expect($category->is_active)->toBeFalse();
});

test('an admin reorders the categories', function () {
    [$first, $second] = Category::factory()->count(2)->sequence(['position' => 1], ['position' => 2])->create();

    $this->actingAs($this->admin)
        ->put(route('admin.categories.reorder'), ['ids' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();

    $this->get(route('admin.categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/categories/index')
            ->where('categories.0.id', $second->id)
            ->where('categories.1.id', $first->id));
});

test('only admins manage categories', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.categories.store'), ['name' => 'Bar'])
        ->assertForbidden();
});
