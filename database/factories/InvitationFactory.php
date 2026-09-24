<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * The token behind the most recently made invitation's link.
     */
    public static ?string $lastToken = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => MembershipRole::Owner,
            'invited_by_id' => User::factory(),
        ];
    }

    /**
     * Issue a link. Read its token from InvitationFactory::$lastToken.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Invitation $invitation): void {
            static::$lastToken = $invitation->issueToken();
        });
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
     * Indicate that the link's expiry has passed.
     */
    public function expired(): static
    {
        return $this->afterMaking(fn (Invitation $invitation) => $invitation->forceFill(['expires_at' => now()->subMinute()]));
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => ['accepted_at' => now()]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => ['declined_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['cancelled_at' => now()]);
    }

    /**
     * Assign outlets to a manager or cashier invitation.
     */
    public function withOutlets(Outlet ...$outlets): static
    {
        return $this->afterCreating(fn (Invitation $invitation) => $invitation->outlets()->attach(
            array_map(fn (Outlet $outlet): string => $outlet->getKey(), $outlets),
        ));
    }
}
