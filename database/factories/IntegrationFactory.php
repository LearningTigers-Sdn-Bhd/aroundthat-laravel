<?php

namespace Database\Factories;

use App\Enums\IntegrationCapability;
use App\Enums\IntegrationType;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => IntegrationType::Pms,
            'capabilities' => array_map(fn (IntegrationCapability $capability): string => $capability->value, IntegrationCapability::cases()),
        ];
    }

    /**
     * Indicate that the integration may only use the given capabilities.
     */
    public function withCapabilities(IntegrationCapability ...$capabilities): static
    {
        return $this->state(fn (array $attributes) => [
            'capabilities' => array_map(fn (IntegrationCapability $capability): string => $capability->value, $capabilities),
        ]);
    }

    /**
     * Indicate that an admin suspended the integration.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now(),
            'suspension_reason' => 'Suspended for testing.',
        ]);
    }

    /**
     * Indicate that the integration's access period has ended.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
