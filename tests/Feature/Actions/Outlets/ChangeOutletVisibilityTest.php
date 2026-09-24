<?php

use App\Actions\Outlets\ChangeOutletVisibility;
use App\Actions\Outlets\UpdateOutletPublicProfile;
use App\Data\Forms\OutletPublicProfileData;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->listed()->create();
});

test('a hidden outlet keeps trading but visitors cannot see it', function () {
    app(ChangeOutletVisibility::class)->hide($this->outlet, 'Photos do not match the place.');

    $this->outlet->refresh();
    expect($this->outlet->isOperational())->toBeTrue();
    expect($this->outlet->isPublic())->toBeFalse();
    expect(Outlet::public()->exists())->toBeFalse();
    $activity = Activity::forSubject($this->outlet)->where('event', 'hidden')->sole();
    expect($activity->reason)->toBe('Photos do not match the place.');
});

test('unhiding shows the outlet to visitors again', function () {
    $outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->listed()->hidden()->create();

    app(ChangeOutletVisibility::class)->unhide($outlet);

    expect($outlet->refresh()->isPublic())->toBeTrue();
    expect($outlet->hidden_reason)->toBeNull();
    expect(Activity::forSubject($outlet)->where('event', 'unhidden')->exists())->toBeTrue();
});

test('hiding a hidden outlet or unhiding a shown one is refused', function () {
    $hidden = Outlet::factory()->approved()->hidden()->create();

    expect(fn () => app(ChangeOutletVisibility::class)->hide($hidden, 'Again.'))
        ->toThrow(ValidationException::class, 'This outlet is already hidden.');
    expect(fn () => app(ChangeOutletVisibility::class)->unhide($this->outlet))
        ->toThrow(ValidationException::class, 'This outlet is not hidden.');
});

test('the owner cannot list a hidden outlet but can unlist it', function () {
    $outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->listed()->hidden()->create(['is_listed' => false]);
    $profile = fn (bool $isListed) => OutletPublicProfileData::from([
        'summary' => $outlet->summary,
        'category_id' => $outlet->category_id,
        'latitude' => $outlet->latitude,
        'longitude' => $outlet->longitude,
        'is_listed' => $isListed,
    ]);

    expect(fn () => app(UpdateOutletPublicProfile::class)->handle($outlet, $profile(true)))
        ->toThrow(ValidationException::class, 'An admin hid this outlet');
    expect($outlet->refresh()->is_listed)->toBeFalse();

    $outlet->forceFill(['is_listed' => true])->saveQuietly();
    app(UpdateOutletPublicProfile::class)->handle($outlet, $profile(false));
    expect($outlet->refresh()->is_listed)->toBeFalse();
});
