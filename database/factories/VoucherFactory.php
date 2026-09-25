<?php

namespace Database\Factories;

use App\Enums\VoucherStatus;
use App\Models\Integration;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\Vouchers\VoucherCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state: an active voucher that expires in a month, like the default offer.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_offer_id' => VoucherOffer::factory(),
            'expires_at' => now()->addMonth(),
            ...self::codeAttributes(VoucherCode::generate()),
        ];
    }

    /**
     * Indicate the voucher's code, so a test can type it at the counter.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => self::codeAttributes($code));
    }

    /**
     * Indicate that the voucher's expiry has passed.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    /**
     * Indicate that every use is spent.
     */
    public function used(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Used,
            'redemption_count' => $count,
        ]);
    }

    /**
     * Indicate that staff voided the voucher.
     */
    public function void(string $reason = 'Issued by mistake.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Void,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }

    /**
     * Indicate that a partner claimed the voucher for a guest.
     */
    public function claimedBy(?Integration $integration = null, string $guestRef = 'guest-1'): static
    {
        return $this->state(fn (array $attributes) => [
            'integration_id' => $integration ?? Integration::factory(),
            'guest_ref_hmac' => hash('sha256', $guestRef),
            'claim_key' => fake()->uuid(),
        ]);
    }

    /**
     * @return array{code: string, code_hash: string, code_prefix: string}
     */
    protected static function codeAttributes(string $code): array
    {
        return [
            'code' => $code,
            'code_hash' => VoucherCode::hash($code),
            'code_prefix' => VoucherCode::prefix($code),
        ];
    }
}
