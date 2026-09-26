<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One redemption at the counter. The page makes a new idempotency key for each voucher it checks.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class RedeemVoucherData extends Data
{
    public function __construct(
        #[Uuid]
        public string $outletId,
        #[Max(64)]
        public string $code,
        public string $billAmount,
        #[Uuid]
        public string $idempotencyKey,
        public ?string $freeItemValue = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'bill_amount' => ['decimal:0,2', 'gt:0', 'lte:9999999999.99'],
            'free_item_value' => ['nullable', 'decimal:0,2', 'gt:0', 'lte:9999999999.99'],
        ];
    }
}
