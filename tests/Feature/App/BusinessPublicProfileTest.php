<?php

use App\Models\Activity;
use App\Models\Business;
use App\Models\Membership;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
});

test('the owner saves the public summary and logs the change', function () {
    $this->actingAs($this->owner->user)
        ->put(route('business.public.update'), ['summary' => 'Family kopitiam since 1978.'])
        ->assertSessionHasNoErrors();

    expect($this->owner->business->refresh()->summary)->toBe('Family kopitiam since 1978.');
    expect(Activity::forSubject($this->owner->business)->latest('id')->first()->attribute_changes['attributes']['summary'])
        ->toBe('Family kopitiam since 1978.');
});

test('the owner adds, replaces and removes the logo', function () {
    $this->actingAs($this->owner->user)
        ->post(route('business.logo.store'), ['kind' => 'logo', 'file' => UploadedFile::fake()->image('a.png'), 'alt_text' => 'Logo'])
        ->assertSessionHasNoErrors();
    $this->post(route('business.logo.store'), ['kind' => 'logo', 'file' => UploadedFile::fake()->image('b.png'), 'alt_text' => 'New logo'])
        ->assertSessionHasNoErrors();

    $this->get(route('business.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('place.logo.alt_text', 'New logo')
            ->where('can.updatePublicProfile', true));
    expect($this->owner->business->images()->active()->count())->toBe(1);

    $this->delete(route('business.logo.destroy'))->assertSessionHasNoErrors();
    expect($this->owner->business->images()->active()->count())->toBe(0);
});

test('managers cannot change the public profile', function () {
    $manager = Membership::factory()->manager()->for($this->owner->business)->create();

    $this->actingAs($manager->user)->put(route('business.public.update'), ['summary' => 'Hi'])->assertForbidden();
});
