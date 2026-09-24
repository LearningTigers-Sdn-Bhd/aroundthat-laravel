<?php

use App\Actions\Outlets\UpdateOutletPublicProfile;
use App\Data\Forms\OutletPublicProfileData;
use App\Enums\TagStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Tag;
use Illuminate\Validation\ValidationException;

function publicProfile(array $overrides = []): OutletPublicProfileData
{
    return OutletPublicProfileData::from([
        'summary' => 'Kopi and kaya toast by the waterfront.',
        'category_id' => Category::factory()->create()->id,
        'latitude' => 5.9804,
        'longitude' => 116.0735,
        ...$overrides,
    ]);
}

function approvedOutlet(): Outlet
{
    return Outlet::factory()->for(Business::factory()->approved())->approved()->create([
        'regular_hours' => ['1' => [['opens' => '09:00', 'closes' => '17:00']]],
    ]);
}

test('saves the public fields and lists an outlet once they are complete', function () {
    $outlet = approvedOutlet();

    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['is_listed' => true, 'website' => 'https://kopi.test']));

    $outlet->refresh();
    expect($outlet->summary)->toBe('Kopi and kaya toast by the waterfront.');
    expect($outlet->website)->toBe('https://kopi.test');
    expect($outlet->isPublic())->toBeTrue();
    expect(Outlet::public()->pluck('id')->all())->toBe([$outlet->id]);
});

test('a Google Maps link fills the coordinates', function () {
    $outlet = approvedOutlet();

    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile([
        'google_maps_url' => 'https://www.google.com/maps/@1.5535,110.3593,17z',
        'latitude' => 5.0,
        'longitude' => 116.0,
    ]));

    expect([(float) $outlet->refresh()->latitude, (float) $outlet->longitude])->toBe([1.5535, 110.3593]);
});

test('an unreadable Google Maps link is refused with its reason', function () {
    app(UpdateOutletPublicProfile::class)->handle(approvedOutlet(), publicProfile(['google_maps_url' => 'https://maps.app.goo.gl/abc']));
})->throws(ValidationException::class, 'shortened link');

test('an outlet cannot be listed before its public fields are complete', function () {
    $outlet = approvedOutlet();

    $outlet->forceFill(['regular_hours' => null])->save();

    expect(fn () => app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile([
        'summary' => null,
        'latitude' => null,
        'longitude' => null,
        'is_listed' => true,
    ])))->toThrow(ValidationException::class, 'Add a summary, the map location and opening hours before listing the outlet.');

    expect($outlet->refresh()->is_listed)->toBeFalse();
});

test('typed tags reuse existing tags and new ones wait for review', function () {
    $outlet = approvedOutlet();
    $halal = Tag::factory()->create(['name' => 'Halal']);

    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['tags' => ['halal', 'Rooftop seating', 'HALAL']]));

    $rooftop = Tag::where('slug', 'rooftop-seating')->sole();
    expect($rooftop->status)->toBe(TagStatus::Pending);
    expect($rooftop->created_by_business_id)->toBe($outlet->business_id);
    expect($outlet->tags()->pluck('name')->all())->toBe(['Halal', 'Rooftop seating']);
    expect(Activity::forSubject($outlet)->where('event', 'tags_changed')->sole()->properties->get('tags'))
        ->toEqual(['old' => [], 'new' => ['Halal', 'Rooftop seating']]);
    expect($halal->refresh()->status)->toBe(TagStatus::Approved);
});

test('rejected and hidden tags cannot be added, but hidden ones already carried stay', function () {
    $outlet = approvedOutlet();
    $hidden = Tag::factory()->inactive()->create(['name' => 'Old label']);
    $outlet->tags()->attach($hidden);
    Tag::factory()->rejected()->create(['name' => 'Cheapest']);

    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['tags' => ['Old label']]));
    expect($outlet->tags()->pluck('tags.id')->all())->toBe([$hidden->id]);

    expect(fn () => app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['tags' => ['cheapest']])))
        ->toThrow(ValidationException::class, '“cheapest” is not available as a tag.');
});

test('a hidden category cannot be newly picked, but stays on an outlet that has it', function () {
    $outlet = approvedOutlet();
    $hidden = Category::factory()->inactive()->create();

    expect(fn () => app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['category_id' => $hidden->id])))
        ->toThrow(ValidationException::class, 'Choose one of the listed categories.');

    $outlet->forceFill(['category_id' => $hidden->id])->save();
    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['category_id' => $hidden->id]));
    expect($outlet->refresh()->category_id)->toBe($hidden->id);
});

test('an outlet waiting for review cannot be changed', function () {
    app(UpdateOutletPublicProfile::class)->handle(Outlet::factory()->pending()->create(), publicProfile());
})->throws(ValidationException::class, 'cannot be changed');

test('saving logs the old and new public fields', function () {
    $outlet = approvedOutlet();
    $outlet->forceFill(['summary' => 'Old summary.'])->saveQuietly();

    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['summary' => 'New summary.']));

    $activity = Activity::forSubject($outlet)->where('event', 'public_profile_changed')->sole();
    expect($activity->log_name)->toBe(Activity::CONTENT_LOG);
    expect($activity->attribute_changes['old']['summary'])->toBe('Old summary.');
    expect($activity->attribute_changes['attributes']['summary'])->toBe('New summary.');
});

test('a category change is logged by name', function () {
    $outlet = approvedOutlet();
    $cafe = Category::factory()->create(['name' => 'Café']);
    $outlet->forceFill(['category_id' => $cafe->id])->saveQuietly();

    app(UpdateOutletPublicProfile::class)->handle($outlet, publicProfile(['category_id' => Category::factory()->create(['name' => 'Bakery'])->id]));

    expect(Activity::forSubject($outlet)->where('event', 'category_changed')->sole()->properties->get('category'))
        ->toEqual(['old' => 'Café', 'new' => 'Bakery']);
    expect(Activity::forSubject($outlet)->get()->pluck('attribute_changes')->filter()->flatMap(fn ($changes) => array_keys($changes['attributes'] ?? []))->all())
        ->not->toContain('category_id');
});
