<?php

namespace App\Data\Admin;

use Spatie\LaravelData\Data;

/**
 * One field an activity changed, from what to what. `old` is null for created records.
 */
class ActivityChangeData extends Data
{
    public function __construct(
        public string $field,
        public mixed $old,
        public mixed $new,
    ) {}
}
