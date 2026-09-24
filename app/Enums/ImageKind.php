<?php

namespace App\Enums;

/**
 * What an image is for. An outlet has one cover and a gallery; a business has one logo.
 */
enum ImageKind: string
{
    case Cover = 'cover';
    case Gallery = 'gallery';
    case Logo = 'logo';

    /**
     * How many active images of this kind one record can have.
     */
    public function limit(): int
    {
        return $this === self::Gallery ? 10 : 1;
    }

    /**
     * Whether a new upload replaces the current image instead of counting against the limit.
     */
    public function replacesCurrent(): bool
    {
        return $this->limit() === 1;
    }

    public function label(): string
    {
        return match ($this) {
            self::Cover => __('Cover photo'),
            self::Gallery => __('Gallery photo'),
            self::Logo => __('Logo'),
        };
    }
}
