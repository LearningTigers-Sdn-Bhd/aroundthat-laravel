<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_id' => Business::factory(),
            'role' => MembershipRole::Owner,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Owner]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Manager]);
    }

    public function cashier(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Cashier]);
    }

    /**
     * Indicate that the business suspended this member.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now(),
            'suspension_reason' => 'Suspended for testing.',
        ]);
    }

    /**
     * Assign outlets to a manager or cashier.
     */
    public function withOutlets(Outlet ...$outlets): static
    {
        return $this->afterCreating(fn (Membership $membership) => $membership->outlets()->attach(
            array_map(fn (Outlet $outlet): string => $outlet->getKey(), $outlets),
        ));
    }
}
