<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

/**
 * The reason an admin or owner gives for rejecting, suspending or removing something. It is kept in the change log.
 */
class ReasonData extends Data
{
    public function __construct(
        #[Max(1000)]
        public string $reason,
    ) {}
}
