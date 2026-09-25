<?php

namespace App\Http\Resources;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A voucher the partner claimed, without its code. Load `offer` first.
 *
 * @mixin Voucher
 */
class VoucherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voucher_offer_id' => $this->voucher_offer_id,
            /** `active`, `used`, `void` or `expired`. */
            'status' => $this->effectiveStatus(),
            'uses_left' => $this->status->value === 'void' || $this->isExpired() ? 0 : $this->usesLeft(),
            'expires_at' => $this->expires_at->min($this->offer->ends_at)->toIso8601String(),
            'claimed_at' => $this->created_at?->toIso8601String(),
            'voided_at' => $this->voided_at?->toIso8601String(),
        ];
    }
}
