<?php

namespace Database\Factories\Concerns;

use App\Enums\OnboardingStatus;

/**
 * Onboarding and suspension states for businesses and outlets.
 */
trait HasOnboardingStates
{
    /**
     * Indicate that the owner submitted it for review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_status' => OnboardingStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate that an admin approved it.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_status' => OnboardingStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that an admin rejected it.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_status' => OnboardingStatus::Rejected,
            'rejection_reason' => 'Missing registration details.',
        ]);
    }

    /**
     * Indicate that an admin suspended it.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now(),
            'suspension_reason' => 'Suspended for testing.',
        ]);
    }
}
