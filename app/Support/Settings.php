<?php

namespace App\Support;

use App\Enums\MediaDisk;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Typed access to the app-wide settings an admin can change. Values are cached until one is saved.
 */
class Settings
{
    protected const string CACHE_KEY = 'settings';

    public function mediaDisk(): MediaDisk
    {
        return MediaDisk::tryFrom((string) $this->get('media_disk')) ?? MediaDisk::Local;
    }

    public function setMediaDisk(MediaDisk $disk): void
    {
        $this->set('media_disk', $disk->value);
    }

    protected function get(string $key): mixed
    {
        /** @var array<string, mixed> $values */
        $values = Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()->pluck('value', 'key')->all());

        return $values[$key] ?? null;
    }

    protected function set(string $key, mixed $value): void
    {
        Setting::query()->firstOrNew(['key' => $key])->fill(['value' => $value])->save();

        Cache::forget(self::CACHE_KEY);
    }
}
