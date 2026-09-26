<?php

namespace App\Http\Resources;

use App\Enums\DiscountType;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A published voucher offer. Load `business` and the public `outlets` first.
 *
 * @mixin VoucherOffer
 */
class VoucherOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** Permanent; use it to claim a voucher. */
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            /** `percentage`, `fixed` (an amount off) or `free_item`. */
            'discount_type' => $this->discount_type === DiscountType::Amount ? 'fixed' : $this->discount_type->value,
            /** The percentage or the amount, as a decimal string. Null for a free item. */
            'discount_value' => $this->discount_value,
            /** The most a percentage takes off one bill, or null for no cap. */
            'max_discount_amount' => $this->max_discount_amount,
            /** The smallest bill the voucher can be used on, or null. */
            'min_spend_amount' => $this->min_spend_amount,
            /** What the guest gets for free, for `free_item` offers. */
            'free_item' => $this->free_item,
            /** ISO 4217, for every amount. */
            'currency' => config('vouchers.currency'),
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            /** How many times one voucher can be used. */
            'uses_per_voucher' => $this->uses_per_voucher,
            /** Days a claimed voucher lasts, capped at `ends_at`. Null: until `ends_at`. */
            'voucher_valid_days' => $this->voucher_valid_days,
            /** The business that runs the offer and pays for the discount. */
            'sponsor' => [
                'slug' => $this->business->slug,
                'name' => $this->business->name,
            ],
            /** The public outlets where the voucher can be used, including other businesses' outlets it sponsors. */
            'outlets' => $this->outlets->map(fn (Outlet $outlet): array => ['slug' => $outlet->slug, 'name' => $outlet->name])->values()->all(),
        ];
    }
}
