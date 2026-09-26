<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A permanent, URL-safe `slug` made from the name. It is set once on create and does not follow later renames.
 *
 * Records without a slug get a unique one (`kopi-corner`, `kopi-corner-2`). Categories and tags set theirs
 * explicitly with slugFor(), because for them the same slug means the same thing, not a clash to number.
 */
trait HasSlug
{
    protected const int SLUG_MAX_LENGTH = 80;

    public static function bootHasSlug(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('slug'))) {
                $model->setAttribute('slug', static::uniqueSlugFor((string) $model->getAttribute('name')));
            }
        });
    }

    /**
     * The slug for a name: Str::slug() for Latin scripts, and lowercase letters and digits joined by dashes for
     * scripts Str::slug() cannot transliterate, such as Chinese or Tamil. Empty when the name has no letter or digit.
     */
    public static function slugFor(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = trim((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', mb_strtolower($name)), '-');
        }

        return trim(mb_substr($slug, 0, self::SLUG_MAX_LENGTH), '-');
    }

    /**
     * A slug no other record uses yet, numbered when the name's slug is taken and random when the name has none.
     */
    public static function uniqueSlugFor(string $name): string
    {
        $base = static::slugFor($name);

        if ($base === '') {
            $base = Str::lower(Str::random(8));
        }

        $slug = $base;

        for ($number = 2; static::query()->where('slug', $slug)->exists(); $number++) {
            $slug = "{$base}-{$number}";
        }

        return $slug;
    }
}
