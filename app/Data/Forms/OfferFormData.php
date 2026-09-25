<?php

namespace App\Data\Forms;

use App\Enums\DiscountType;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * The terms of a voucher offer as the owner enters them. Dates are local to the business, as `2026-10-01T09:00`.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class OfferFormData extends Data
{
    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i';

    public function __construct(
        #[Max(255)]
        public string $name,
        public DiscountType $discountType,
        #[DateFormat(self::DATE_TIME_FORMAT)]
        public string $startsAt,
        #[DateFormat(self::DATE_TIME_FORMAT)]
        public string $endsAt,
        #[Max(2000)]
        public ?string $description = null,
        public ?string $discountValue = null,
        public ?string $maxDiscountAmount = null,
        public ?string $minSpendAmount = null,
        #[Max(255)]
        public ?string $freeItem = null,
        #[Between(1, 3650)]
        public ?int $voucherValidDays = null,
        #[Between(1, 1_000_000)]
        public ?int $voucherLimit = null,
        #[Between(1, 100)]
        public int $usesPerVoucher = 1,
    ) {}

    /**
     * The discount fields each type needs. Fields another type uses are dropped by toModelAttributes().
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $type = DiscountType::tryFrom((string) ($context->payload['discount_type'] ?? ''));

        return [
            'discount_value' => match ($type) {
                DiscountType::Percentage => ['required', 'decimal:0,2', 'gt:0', 'lte:100'],
                DiscountType::Amount => ['required', 'decimal:0,2', 'gt:0', 'lte:99999999.99'],
                default => ['nullable'],
            },
            'max_discount_amount' => $type === DiscountType::Percentage ? ['nullable', 'decimal:0,2', 'gt:0', 'lte:99999999.99'] : ['nullable'],
            'min_spend_amount' => ['nullable', 'decimal:0,2', 'gt:0', 'lte:99999999.99'],
            'free_item' => $type === DiscountType::FreeItem ? ['required'] : ['nullable'],
            'ends_at' => ['after:starts_at'],
        ];
    }

    /**
     * The values as model attributes, with the dates read in the business's time zone.
     *
     * @return array<string, mixed>
     */
    public function toModelAttributes(string $timezone): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountType === DiscountType::FreeItem ? null : $this->discountValue,
            'max_discount_amount' => $this->discountType === DiscountType::Percentage ? $this->maxDiscountAmount : null,
            'min_spend_amount' => $this->minSpendAmount,
            'free_item' => $this->discountType === DiscountType::FreeItem ? $this->freeItem : null,
            'starts_at' => self::parseLocal($this->startsAt, $timezone),
            'ends_at' => self::parseLocal($this->endsAt, $timezone),
            'voucher_valid_days' => $this->voucherValidDays,
            'voucher_limit' => $this->voucherLimit,
            'uses_per_voucher' => $this->usesPerVoucher,
        ];
    }

    public static function parseLocal(string $value, string $timezone): Carbon
    {
        return Carbon::createFromFormat(self::DATE_TIME_FORMAT, $value, $timezone)->startOfMinute()->utc();
    }
}
