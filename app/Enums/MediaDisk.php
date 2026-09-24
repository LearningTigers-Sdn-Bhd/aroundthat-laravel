<?php

namespace App\Enums;

/**
 * Where newly uploaded images are stored. Each image remembers its own disk, so switching only affects new uploads.
 */
enum MediaDisk: string
{
    case Local = 'local';
    case R2 = 'r2';

    public function label(): string
    {
        return match ($this) {
            self::Local => __('This server'),
            self::R2 => __('Cloudflare R2'),
        };
    }

    /**
     * The filesystem disk in config/filesystems.php.
     */
    public function diskName(): string
    {
        return match ($this) {
            self::Local => 'public',
            self::R2 => 'r2',
        };
    }

    /**
     * Whether the disk has the credentials it needs. R2 keys live in the environment only.
     */
    public function isConfigured(): bool
    {
        if ($this === self::Local) {
            return true;
        }

        return collect(['key', 'secret', 'bucket', 'endpoint', 'url'])
            ->every(fn (string $option): bool => filled(config("filesystems.disks.r2.{$option}")));
    }
}
