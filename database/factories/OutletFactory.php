<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Category;
use App\Models\Outlet;
use Database\Factories\Concerns\HasOnboardingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outlet>
 */
class OutletFactory extends Factory
{
    use HasOnboardingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->company().' '.fake()->city(),
            'contact_email' => fake()->companyEmail(),
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => 'Sabah',
            'postcode' => fake()->numerify('#####'),
            'country_code' => 'MY',
            'timezone' => 'Asia/Kuala_Lumpur',
        ];
    }

    /**
     * Indicate that the owner completed the public fields and listed the outlet.
     */
    public function listed(): static
    {
        return $this->state(fn (array $attributes) => [
            'summary' => fake()->sentence(),
            'category_id' => Category::factory(),
            'latitude' => fake()->latitude(1, 7),
            'longitude' => fake()->longitude(109, 119),
            'regular_hours' => array_fill_keys(['1', '2', '3', '4', '5', '6', '7'], [['opens' => '09:00', 'closes' => '22:00']]),
            'is_listed' => true,
        ]);
    }

    /**
     * Indicate that the outlet has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
