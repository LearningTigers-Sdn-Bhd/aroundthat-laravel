<?php

use App\Models\Business;
use App\Models\Image;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner previews the public page as a visitor would see it', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-24 02:30', 'UTC'));
    $owner = Membership::factory()->owner()->for(Business::factory()->approved()->state(['summary' => 'Since 1978.']))->create();
    $outlet = Outlet::factory()->for($owner->business)->approved()->listed()->create([
        'name' => 'Kopi Corner',
        'regular_hours' => ['4' => [['opens' => '09:00', 'closes' => '17:00']]],
    ]);
    Image::factory()->for($outlet, 'imageable')->create(['kind' => 'cover', 'alt_text' => 'Shop front']);

    $this->actingAs($owner->user)
        ->get(route('outlets.preview', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/outlets/preview')
            ->where('preview.name', 'Kopi Corner')
            ->where('preview.is_open_now', true)
            ->where('preview.closes_at', '17:00')
            ->where('preview.images.0.alt_text', 'Shop front')
            ->where('preview.business.summary', 'Since 1978.')
            ->where('preview.place.is_public', true));
});

test('managers cannot open the preview', function () {
    $manager = Membership::factory()->manager()->create();

    $this->actingAs($manager->user)
        ->get(route('outlets.preview', Outlet::factory()->for($manager->business)->create()))
        ->assertForbidden();
});

test('admins see the public page on the outlet page', function () {
    $outlet = Outlet::factory()->listed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.outlets.show', $outlet))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/outlets/show')
            ->where('preview.place.summary', $outlet->summary)
            ->where('preview.place.is_listed', true));
});
