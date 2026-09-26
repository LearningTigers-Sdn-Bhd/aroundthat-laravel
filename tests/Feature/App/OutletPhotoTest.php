<?php

use App\Models\Business;
use App\Models\Image;
use App\Models\Membership;
use App\Models\Outlet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $this->outlet = Outlet::factory()->for($this->owner->business)->approved()->create();
});

test('the owner uploads, describes, orders and removes photos', function () {
    $this->actingAs($this->owner->user)
        ->post(route('outlets.photos.store', $this->outlet), [
            'kind' => 'gallery',
            'file' => UploadedFile::fake()->image('a.jpg'),
            'alt_text' => 'First',
        ])
        ->assertSessionHasNoErrors();
    $this->post(route('outlets.photos.store', $this->outlet), [
        'kind' => 'gallery',
        'file' => UploadedFile::fake()->image('b.jpg'),
        'alt_text' => 'Second',
    ]);
    [$first, $second] = $this->outlet->images()->orderBy('position')->get();

    $this->put(route('outlets.photos.update', [$this->outlet, $first]), ['alt_text' => 'First, renamed'])->assertSessionHasNoErrors();
    $this->put(route('outlets.photos.reorder', $this->outlet), ['ids' => [$second->id, $first->id]])->assertSessionHasNoErrors();

    $this->get(route('outlets.photos.index', $this->outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/photos')
            ->where('images.0.id', $second->id)
            ->where('images.1.alt_text', 'First, renamed'));

    $this->delete(route('outlets.photos.destroy', [$this->outlet, $second]))->assertSessionHasNoErrors();
    expect($this->outlet->images()->active()->count())->toBe(1);
});

test('uploads must be images', function () {
    $this->actingAs($this->owner->user)
        ->post(route('outlets.photos.store', $this->outlet), [
            'kind' => 'gallery',
            'file' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
            'alt_text' => 'Menu',
        ])
        ->assertSessionHasErrors(['file' => 'Upload a JPEG, PNG or WebP image.']);
});

test('a photo of another outlet is not found', function () {
    $other = Image::factory()->create();

    $this->actingAs($this->owner->user)
        ->delete(route('outlets.photos.destroy', [$this->outlet, $other]))
        ->assertNotFound();
});

test('managers cannot change photos', function () {
    $manager = Membership::factory()->manager()->for($this->owner->business)->create();

    $this->actingAs($manager->user)->get(route('outlets.photos.index', $this->outlet))->assertForbidden();
});
