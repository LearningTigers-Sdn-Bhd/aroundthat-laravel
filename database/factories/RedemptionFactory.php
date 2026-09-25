<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redemption>
 */
class RedemptionFactory extends Factory
{
    /**
     * Define the model's default state: RM 10.00 off a RM 100.00 bill, just now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'outlet_id' => Outlet::factory(),
            'user_id' => User::factory(),
            'bill_amount' => '100.00',
            'discount_amount' => '10.00',
            'idempotency_key' => fake()->uuid(),
            'redeemed_at' => now(),
        ];
    }

    /**
     * Indicate that the counter cancelled the redemption.
     */
    public function cancelled(string $reason = 'Wrong bill.'): static
    {
        return $this->state(fn (array $attributes) => [
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }
}
