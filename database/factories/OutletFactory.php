<?php

namespace Database\Factories;

use App\Models\Business;
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
     * Indicate that the outlet has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
