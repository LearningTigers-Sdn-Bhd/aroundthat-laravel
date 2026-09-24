<?php

namespace Database\Factories;

use App\Enums\TagStatus;
use App\Models\Business;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'status' => TagStatus::Approved,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that an owner created the tag and it waits for an admin.
     */
    public function pendingFrom(?Business $business = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TagStatus::Pending,
            'created_by_business_id' => $business ?? Business::factory(),
        ]);
    }

    /**
     * Indicate that an admin rejected the tag.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TagStatus::Rejected,
        ]);
    }

    /**
     * Indicate that owners can no longer pick the tag.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
