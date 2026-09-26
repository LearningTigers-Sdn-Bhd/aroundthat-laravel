<?php

namespace Database\Factories;

use App\Models\Business;
use Database\Factories\Concerns\HasOnboardingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
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
            'name' => fake()->company(),
            'contact_email' => fake()->unique()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'timezone' => 'Asia/Kuala_Lumpur',
        ];
    }
}
