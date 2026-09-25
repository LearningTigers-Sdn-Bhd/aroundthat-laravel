<?php

namespace App\Data;

use App\Models\Voucher;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * A voucher as its business's members see it: never the code, only its first four characters.
 * Load `offer` and `integration` first.
 */
#[MapName(SnakeCaseMapper::class)]
class VoucherData extends Data
{
    public function __construct(
        public string $id,
        public string $codePrefix,
        #[LiteralTypeScriptType("'active' | 'used' | 'void' | 'expired'")]
        public string $status,
        public int $redemptionCount,
        public int $usesPerVoucher,
        public CarbonInterface $expiresAt,
        public ?CarbonInterface $createdAt,
        public ?string $claimedByName,
        public ?CarbonInterface $voidedAt,
        public ?string $voidReason,
    ) {}

    public static function fromModel(Voucher $voucher): self
    {
        return new self(
            id: $voucher->id,
            codePrefix: $voucher->code_prefix,
            status: $voucher->effectiveStatus(),
            redemptionCount: $voucher->redemption_count,
            usesPerVoucher: $voucher->offer->uses_per_voucher,
            expiresAt: $voucher->expires_at,
            createdAt: $voucher->created_at,
            claimedByName: $voucher->integration?->name,
            voidedAt: $voucher->voided_at,
            voidReason: $voucher->void_reason,
        );
    }
}
