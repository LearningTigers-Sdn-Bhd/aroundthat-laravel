<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Something on the dashboard's to-do list, with where to go to deal with it.
 */
class DashboardAttentionData extends Data
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
    ) {}
}
