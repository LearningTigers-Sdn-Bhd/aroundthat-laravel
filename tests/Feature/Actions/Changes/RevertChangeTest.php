<?php

use App\Actions\Changes\RevertChange;
use App\Actions\Images\ManageImages;
use App\Actions\Outlets\UpdateOpeningHours;
use App\Actions\Outlets\UpdateOutlet;
use App\Actions\Outlets\UpdateOutletPublicProfile;
use App\Data\Forms\OpeningHoursData;
use App\Data\Forms\OutletDetailsData;
use App\Data\Forms\OutletPublicProfileData;
use App\Enums\ImageKind;
use App\Jobs\NotifyPlaceModeration;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Category;
use App\Models\Image;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->category = Category::factory()->create(['name' => 'Café']);
    $this->outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->create([
        'summary' => 'Old summary.',
        'category_id' => $this->category->id,
        'latitude' => 5.9804,
        'longitude' => 116.0735,
        'regular_hours' => ['1' => [['opens' => '09:00', 'closes' => '17:00']]],
    ]);
});

/**
 * Save the outlet's public page as its owner would, keeping the fields the test does not name.
 *
 * @param  array<string, mixed>  $overrides
 */
function savePublicPage(Outlet $outlet, array $overrides = []): void
{
    $outlet->refresh();

    app(UpdateOutletPublicProfile::class)->handle($outlet, OutletPublicProfileData::from([
        'summary' => $outlet->summary,
        'category_id' => $outlet->category_id,
        'latitude' => $outlet->latitude,
        'longitude' => $outlet->longitude,
        'is_listed' => $outlet->is_listed,
        'tags' => $outlet->tags()->pluck('name')->all(),
        ...$overrides,
    ]));
}

function lastChange(Outlet $outlet, string $event): Activity
{
    return Activity::forSubject($outlet)->where('event', $event)->latest('id')->firstOrFail();
}

test('reverting a field change restores the old values and logs the reason', function () {
    Queue::fake([NotifyPlaceModeration::class]);
    savePublicPage($this->outlet, ['summary' => 'New summary.']);
    $change = lastChange($this->outlet, 'public_profile_changed');

    app(RevertChange::class)->revert($this->admin, $change, 'Summary breaks the house rules.');

    expect($this->outlet->refresh()->summary)->toBe('Old summary.');
    $revert = Activity::forSubject($this->outlet)->where('event', 'reverted')->sole();
    expect($revert->reason)->toBe('Summary breaks the house rules.');
    expect($revert->properties->get('reverts'))->toBe(['id' => $change->id, 'event' => 'public_profile_changed']);
    expect($revert->attribute_changes['attributes']['summary'])->toBe('Old summary.');
    $change->refresh();
    expect($change->reverted_by_activity_id)->toBe($revert->id);
    expect($change->reverted_at)->not->toBeNull();
    expect($change->reviewed_by_id)->toBe($this->admin->id);
    Queue::assertPushed(NotifyPlaceModeration::class, fn (NotifyPlaceModeration $job) => $job->place->is($this->outlet)
        && $job->action === 'reverted'
        && $job->reason === 'Summary breaks the house rules.'
        && $job->changeEvent === 'public_profile_changed');
});

test('reverting outlet details puts the old name back', function () {
    app(UpdateOutlet::class)->handle($this->outlet, OutletDetailsData::from([
        ...$this->outlet->only(['contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postcode', 'country_code', 'timezone']),
        'name' => 'Renamed',
    ]));
    $oldName = lastChange($this->outlet, 'details_changed')->attribute_changes['old']['name'];

    app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'details_changed'), 'Name is misleading.');

    expect($this->outlet->refresh()->name)->toBe($oldName);
});

test('a later edit to the same field blocks the revert and changes nothing', function () {
    Queue::fake([NotifyPlaceModeration::class]);
    savePublicPage($this->outlet, ['summary' => 'Second.']);
    $change = lastChange($this->outlet, 'public_profile_changed');
    savePublicPage($this->outlet, ['summary' => 'Third.']);

    expect(app(RevertChange::class)->conflicts($change))->toBe(['Summary was changed again.']);
    expect(fn () => app(RevertChange::class)->revert($this->admin, $change, 'Too late.'))
        ->toThrow(ValidationException::class, 'Summary was changed again.');
    expect($this->outlet->refresh()->summary)->toBe('Third.');
    expect($change->refresh()->reverted_at)->toBeNull();
    Queue::assertNotPushed(NotifyPlaceModeration::class);
});

test('a change cannot be reverted twice', function () {
    savePublicPage($this->outlet, ['summary' => 'New summary.']);
    $change = lastChange($this->outlet, 'public_profile_changed');
    app(RevertChange::class)->revert($this->admin, $change, 'First revert.');

    app(RevertChange::class)->revert($this->admin, $change->refresh(), 'Second revert.');
})->throws(ValidationException::class, 'This change was already reverted.');

test('an entry without the detail to undo it cannot be reverted', function () {
    $change = app(AuditTrail::class)->content(fn () => app(AuditTrail::class)->record($this->outlet, 'tags_changed', null, [
        'tags' => ['old' => ['Halal'], 'new' => []],
    ]));

    expect(app(RevertChange::class)->conflicts($change))->toBe(['This change did not keep enough detail to be reverted.']);
});

test('activity outside the content log cannot be reverted', function () {
    $change = app(AuditTrail::class)->record($this->outlet, 'suspended', 'Unpaid fees.');

    expect(app(RevertChange::class)->conflicts($change))->toBe(['Only changes to public content can be reverted.']);
});

test('reverting a category change puts the old category back', function () {
    savePublicPage($this->outlet, ['category_id' => Category::factory()->create(['name' => 'Bakery'])->id]);

    app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'category_changed'), 'Wrong category.');

    expect($this->outlet->refresh()->category_id)->toBe($this->category->id);
    expect(Activity::forSubject($this->outlet)->where('event', 'reverted')->sole()->properties->get('category'))
        ->toEqual(['old' => 'Bakery', 'new' => 'Café']);
});

test('reverting a tag change puts the old tags back and a newer tag change blocks it', function () {
    savePublicPage($this->outlet, ['tags' => ['Halal', 'Wifi']]);
    savePublicPage($this->outlet, ['tags' => ['Halal']]);
    $removedWifi = lastChange($this->outlet, 'tags_changed');
    $addedTags = Activity::forSubject($this->outlet)->where('event', 'tags_changed')->oldest('id')->first();

    expect(app(RevertChange::class)->conflicts($addedTags))->toBe(['The tags were changed again.']);

    app(RevertChange::class)->revert($this->admin, $removedWifi, 'Wifi is real.');

    expect($this->outlet->tags()->orderBy('name')->pluck('name')->all())->toBe(['Halal', 'Wifi']);
});

test('reverting an hours change restores the week and the special dates', function () {
    $this->travelTo('2026-09-24 10:00');
    $this->outlet->dateExceptions()->create(['date' => '2026-12-25', 'is_closed' => true, 'note' => 'Christmas']);
    app(UpdateOpeningHours::class)->handle($this->outlet, OpeningHoursData::from([
        'regular_hours' => ['2' => [['opens' => '10:00', 'closes' => '22:00']]],
        'date_exceptions' => [['date' => '2026-10-20', 'is_closed' => true]],
    ]));

    app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'hours_changed'), 'Hours look wrong.');
    app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'date_exceptions_changed'), 'Dates look wrong.');

    $this->outlet->refresh();
    expect($this->outlet->regular_hours['1'])->toBe([['opens' => '09:00', 'closes' => '17:00']]);
    expect($this->outlet->regular_hours['2'] ?? [])->toBe([]);
    expect($this->outlet->dateExceptions()->orderBy('date')->get()->map->describe()->all())->toBe(['2026-12-25: closed (Christmas)']);
});

test('a revert that leaves a listed outlet incomplete also unlists it', function () {
    $this->outlet->forceFill(['summary' => null])->saveQuietly();
    savePublicPage($this->outlet, ['summary' => 'Now complete.', 'is_listed' => true]);

    app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'public_profile_changed'), 'Summary is spam.');

    $this->outlet->refresh();
    expect($this->outlet->summary)->toBeNull();
    expect($this->outlet->is_listed)->toBeFalse();
});

describe('photos', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    test('reverting an added photo removes it', function () {
        $image = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Rude sign');

        app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'image_added'), 'Offensive photo.');

        expect($image->refresh()->removed_at)->not->toBeNull();
    });

    test('reverting a removed photo brings it back while its file exists', function () {
        $image = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Storefront');
        app(ManageImages::class)->remove($image);

        app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'image_removed'), 'Removed by mistake.');

        expect($image->refresh()->removed_at)->toBeNull();
    });

    test('a removed photo whose file was pruned cannot be brought back', function () {
        $image = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Storefront');
        app(ManageImages::class)->remove($image);
        Storage::disk('public')->delete($image->path);

        expect(app(RevertChange::class)->conflicts(lastChange($this->outlet, 'image_removed')))
            ->toBe(['A removed photo’s file was already deleted.']);
    });

    test('reverting a photo description puts the old text back', function () {
        $image = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Storefront');
        app(ManageImages::class)->describe($image, 'Rude words');

        app(RevertChange::class)->revert($this->admin, lastChange($this->outlet, 'image_changed'), 'Rude description.');

        expect($image->refresh()->alt_text)->toBe('Storefront');
    });

    test('bringing back an old cover while a newer cover is up goes over the limit', function () {
        $first = app(ManageImages::class)->add($this->outlet, ImageKind::Cover, UploadedFile::fake()->image('a.jpg'), 'First');
        app(ManageImages::class)->remove($first);
        $removal = lastChange($this->outlet, 'image_removed');
        Image::factory()->for($this->outlet, 'imageable')->create(['kind' => ImageKind::Cover]);

        expect(app(RevertChange::class)->conflicts($removal))
            ->toBe(['Reverting would go over the limit of 1 for cover photo.']);
    });
});
