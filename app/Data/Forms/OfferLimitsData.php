<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What an owner may still change once vouchers are issued (see VoucherOffer::EDITABLE_AFTER_ISSUE).
 */
#[MapName(SnakeCaseMapper::class)]
class OfferLimitsData extends Data
{
    public function __construct(
        #[DateFormat(OfferFormData::DATE_TIME_FORMAT)]
        public string $endsAt,
        #[Max(2000)]
        public ?string $description = null,
        #[Between(1, 1_000_000)]
        public ?int $voucherLimit = null,
    ) {}

    /**
     * The values as model attributes, with the end date read in the business's time zone.
     *
     * @return array<string, mixed>
     */
    public function toModelAttributes(string $timezone): array
    {
        return [
            'description' => $this->description,
            'ends_at' => OfferFormData::parseLocal($this->endsAt, $timezone),
            'voucher_limit' => $this->voucherLimit,
        ];
    }
}
