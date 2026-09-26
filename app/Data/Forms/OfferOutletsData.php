<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The business's own outlets where an offer's vouchers can be redeemed. SyncOfferOutlets checks they belong to it.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class OfferOutletsData extends Data
{
    /**
     * @param  list<string>  $outletIds
     */
    public function __construct(
        public array $outletIds,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'outlet_ids' => ['required', 'array', 'min:1', 'max:200'],
            'outlet_ids.*' => ['uuid', 'distinct'],
        ];
    }
}
