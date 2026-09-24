<?php

namespace App\Models;

use App\Enums\ImageKind;
use Database\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded photo or logo. The file never changes; it stays on the disk it was uploaded to, even if an admin
 * later switches where new images go. A removed image keeps its file for 30 days, so a change can still be undone.
 *
 * @property string $id
 * @property string $imageable_type
 * @property string $imageable_id
 * @property ImageKind $kind
 * @property string $disk
 * @property string $path
 * @property string $alt_text
 * @property int $width
 * @property int $height
 * @property int $position
 * @property Carbon|null $removed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['alt_text', 'position'])]
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory, HasUuids, Prunable;

    public const int KEEP_REMOVED_DAYS = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ImageKind::class,
            'width' => 'integer',
            'height' => 'integer',
            'position' => 'integer',
            'removed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * What the change log keeps about the image, so a reverted change can put it back.
     *
     * @return array{alt_text: string, position: int, removed: bool}
     */
    public function toSnapshot(): array
    {
        return [
            'alt_text' => $this->alt_text,
            'position' => $this->position,
            'removed' => $this->removed_at !== null,
        ];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('removed_at'));
    }

    /**
     * Images removed long enough ago that their files can go.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('removed_at', '<', now()->subDays(self::KEEP_REMOVED_DAYS));
    }

    protected function pruning(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
