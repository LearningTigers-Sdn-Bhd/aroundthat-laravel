<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A date an outlet keeps different hours, such as a public holiday. A closed date has no periods.
 * Changes are logged on the outlet by UpdateOpeningHours.
 *
 * @property string $id
 * @property string $outlet_id
 * @property Carbon $date
 * @property bool $is_closed
 * @property list<array{opens: string, closes: string}>|null $periods
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['date', 'is_closed', 'periods', 'note'])]
class OutletDateException extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
            'periods' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Outlet, $this>
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * The row as the change log keeps it, so a reverted change can recreate it.
     *
     * @return array{date: string, is_closed: bool, periods: list<array{opens: string, closes: string}>|null, note: string|null}
     */
    public function toSnapshot(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'is_closed' => $this->is_closed,
            'periods' => $this->periods,
            'note' => $this->note,
        ];
    }

    /**
     * How the change log names this date, such as "2026-12-25: closed (Christmas)".
     */
    public function describe(): string
    {
        $hours = $this->is_closed
            ? 'closed'
            : collect($this->periods ?? [])->map(fn (array $period): string => "{$period['opens']}–{$period['closes']}")->join(', ');

        return $this->date->toDateString().': '.$hours.($this->note ? " ({$this->note})" : '');
    }
}
