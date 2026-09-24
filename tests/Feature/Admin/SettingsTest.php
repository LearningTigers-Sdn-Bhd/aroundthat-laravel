<?php

use App\Enums\MediaDisk;
use App\Models\User;
use App\Support\Settings;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function configureR2(): void
{
    config([
        'filesystems.disks.r2.key' => 'key',
        'filesystems.disks.r2.secret' => 'secret',
        'filesystems.disks.r2.bucket' => 'aroundthat',
        'filesystems.disks.r2.endpoint' => 'https://account.r2.cloudflarestorage.com',
        'filesystems.disks.r2.url' => 'https://media.aroundthat.test',
    ]);
}

test('new images are stored locally until an admin chooses otherwise', function () {
    expect(app(Settings::class)->mediaDisk())->toBe(MediaDisk::Local);

    $this->actingAs($this->admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/settings/index')
            ->where('mediaDisk', 'local')
            ->where('mediaDisks.1.configured', false));
});

test('an admin switches image storage to r2 once it is set up', function () {
    configureR2();

    $this->actingAs($this->admin)
        ->put(route('admin.settings.media.update'), ['media_disk' => 'r2'])
        ->assertSessionHasNoErrors();

    expect(app(Settings::class)->mediaDisk())->toBe(MediaDisk::R2);
});

test('r2 cannot be chosen before its keys are set', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.media.update'), ['media_disk' => 'r2'])
        ->assertSessionHasErrors('media_disk');

    expect(app(Settings::class)->mediaDisk())->toBe(MediaDisk::Local);
});

test('only admins can change settings', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.settings.media.update'), ['media_disk' => 'local'])
        ->assertForbidden();
});
