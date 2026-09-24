<?php

use App\Enums\TagStatus;
use App\Models\Business;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('the pending filter lists tags owners created, with their business', function () {
    $business = Business::factory()->create(['name' => 'Kedai Aminah']);
    $pending = Tag::factory()->pendingFrom($business)->create();
    Tag::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.tags.index', ['filter' => ['status' => 'pending']]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tags/index')
            ->has('tags.data', 1)
            ->where('tags.data.0.id', $pending->id)
            ->where('tags.data.0.created_by_business_name', 'Kedai Aminah'));
});

test('the dashboard counts tags waiting for review', function () {
    Tag::factory()->pendingFrom()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('pendingTagCount', 2));
});

test('an admin adds an approved tag and approves an owner tag', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.tags.store'), ['name' => 'Pet friendly', 'is_active' => '1'])
        ->assertSessionHasNoErrors();
    expect(Tag::where('slug', 'pet-friendly')->sole()->status)->toBe(TagStatus::Approved);

    $pending = Tag::factory()->pendingFrom()->create();
    $this->post(route('admin.tags.approve', $pending))->assertSessionHasNoErrors();
    expect($pending->refresh()->status)->toBe(TagStatus::Approved);
});

test('rejecting a tag needs a reason', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.tags.reject', Tag::factory()->pendingFrom()->create()))
        ->assertSessionHasErrors('reason');
});

test('an admin merges a tag into another', function () {
    $target = Tag::factory()->create();
    $duplicate = Tag::factory()->pendingFrom()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.tags.merge', $duplicate), ['target_id' => $target->id])
        ->assertSessionHasNoErrors();

    expect($duplicate->refresh()->merged_into_id)->toBe($target->id);
});

test('only admins manage tags', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.tags.approve', Tag::factory()->pendingFrom()->create()))
        ->assertForbidden();
});
