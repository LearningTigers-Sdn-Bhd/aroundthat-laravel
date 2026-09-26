<?php

use App\Actions\Images\ManageImages;
use App\Enums\ImageKind;
use App\Enums\MediaDisk;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Image;
use App\Models\Outlet;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('public');
    $this->outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->create();
});

test('stores a photo on the chosen disk with its size', function () {
    $image = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('river.jpg', 1200, 800), 'River view');

    expect($image->disk)->toBe('public');
    expect([$image->width, $image->height])->toBe([1200, 800]);
    Storage::disk('public')->assertExists($image->path);
    expect(Activity::forSubject($this->outlet)->where('event', 'image_added')->sole()->properties->get('images'))
        ->toEqual(['old' => [], 'new' => ['Gallery photo: River view']]);
});

test('new uploads follow the admin setting and old images keep their disk', function () {
    $old = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Before');

    Storage::fake('r2');
    app(Settings::class)->setMediaDisk(MediaDisk::R2);
    $new = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('b.jpg'), 'After');

    expect($old->refresh()->disk)->toBe('public');
    expect($new->disk)->toBe('r2');
    Storage::disk('r2')->assertExists($new->path);
});

test('a file that only claims to be an image is refused', function () {
    app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->createWithContent('logo.png', 'not an image'), 'Fake');
})->throws(ValidationException::class, 'Upload a JPEG, PNG or WebP image.');

test('a new cover replaces the current one', function () {
    $first = app(ManageImages::class)->add($this->outlet, ImageKind::Cover, UploadedFile::fake()->image('a.jpg'), 'First');
    $second = app(ManageImages::class)->add($this->outlet, ImageKind::Cover, UploadedFile::fake()->image('b.jpg'), 'Second');

    expect($first->refresh()->removed_at)->not->toBeNull();
    expect($this->outlet->images()->active()->pluck('id')->all())->toBe([$second->id]);
});

test('the gallery holds at most ten photos', function () {
    Image::factory()->count(10)->for($this->outlet, 'imageable')->create();

    app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'One too many');
})->throws(ValidationException::class, 'at most 10 gallery photos');

test('an outlet cannot have a logo', function () {
    app(ManageImages::class)->add($this->outlet, ImageKind::Logo, UploadedFile::fake()->image('a.jpg'), 'Logo');
})->throws(ValidationException::class, 'does not belong here');

test('removing hides a photo and its file is pruned after 30 days', function () {
    $image = app(ManageImages::class)->add($this->outlet, ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Gone soon');

    app(ManageImages::class)->remove($image);
    expect($image->refresh()->removed_at)->not->toBeNull();

    $this->artisan('model:prune', ['--model' => [Image::class]]);
    Storage::disk('public')->assertExists($image->path);

    $image->forceFill(['removed_at' => now()->subDays(31)])->save();
    $this->artisan('model:prune', ['--model' => [Image::class]]);
    Storage::disk('public')->assertMissing($image->path);
    expect(Image::find($image->id))->toBeNull();
});

test('photos of an outlet waiting for review cannot change', function () {
    app(ManageImages::class)->add(Outlet::factory()->pending()->create(), ImageKind::Gallery, UploadedFile::fake()->image('a.jpg'), 'Photo');
})->throws(ValidationException::class, 'cannot be changed');
