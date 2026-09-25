<?php

namespace App\Data;

use App\Models\VoucherOffer;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * A running or scheduled offer on the dashboard, with how many vouchers it has issued.
 */
#[MapName(SnakeCaseMapper::class)]
class DashboardOfferData extends Data
{
    /** An offer ending within this many days is flagged as ending soon. */
    public const int ENDING_SOON_DAYS = 7;

    /**
     * @param  string  $state  What matters most right now; see VoucherOffer::state().
     */
    public function __construct(
        public string $id,
        public string $name,
        #[LiteralTypeScriptType("'active' | 'paused' | 'scheduled'")]
        public string $state,
        public int $issuedCount,
        public ?int $voucherLimit,
        public CarbonInterface $endsAt,
        public bool $endsSoon,
    ) {}

    public static function fromModel(VoucherOffer $offer): self
    {
        return new self(
            id: $offer->id,
            name: $offer->name,
            state: $offer->state(),
            issuedCount: $offer->issued_count,
            voucherLimit: $offer->voucher_limit,
            endsAt: $offer->ends_at,
            endsSoon: $offer->ends_at->lessThanOrEqualTo(now()->addDays(self::ENDING_SOON_DAYS)),
        );
    }
}
