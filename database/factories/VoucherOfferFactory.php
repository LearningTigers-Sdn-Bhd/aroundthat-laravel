<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherOffer>
 */
class VoucherOfferFactory extends Factory
{
    /**
     * Define the model's default state: a draft 10% offer that runs for a month.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'discount_type' => DiscountType::Percentage,
            'discount_value' => '10.00',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => OfferStatus::Draft,
        ];
    }

    /**
     * Indicate that the owner runs the offer and its business is approved, so guests can claim it.
     */
    public function active(): static
    {
        return $this->for(Business::factory()->approved())->state(fn (array $attributes) => [
            'status' => OfferStatus::Active,
        ]);
    }

    /**
     * Indicate that the owner paused the offer.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Paused,
        ]);
    }

    /**
     * Indicate that an admin hid the offer.
     */
    public function hidden(string $reason = 'Misleading terms.'): static
    {
        return $this->state(fn (array $attributes) => [
            'hidden_at' => now(),
            'hidden_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the offer's end date has passed.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate a percentage off, optionally capped.
     */
    public function percentage(string $value = '10.00', ?string $max = null): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => DiscountType::Percentage,
            'discount_value' => $value,
            'max_discount_amount' => $max,
            'free_item' => null,
        ]);
    }

    /**
     * Indicate a fixed amount off.
     */
    public function amount(string $value = '5.00'): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => DiscountType::Amount,
            'discount_value' => $value,
            'max_discount_amount' => null,
            'free_item' => null,
        ]);
    }

    /**
     * Indicate one free item.
     */
    public function freeItem(string $item = 'Iced latte'): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => DiscountType::FreeItem,
            'discount_value' => null,
            'max_discount_amount' => null,
            'free_item' => $item,
        ]);
    }

    /**
     * Indicate that vouchers can be redeemed at the given outlets.
     */
    public function at(Outlet ...$outlets): static
    {
        return $this->afterCreating(fn (VoucherOffer $offer) => $offer->outlets()->attach(array_map(fn (Outlet $outlet): string => $outlet->id, $outlets)));
    }
}
