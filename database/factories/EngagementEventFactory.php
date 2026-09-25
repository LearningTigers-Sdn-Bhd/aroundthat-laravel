<?php

namespace Database\Factories;

use App\Enums\EngagementEventType;
use App\Models\EngagementEvent;
use App\Models\Integration;
use App\Models\Outlet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EngagementEvent>
 */
class EngagementEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'integration_id' => Integration::factory(),
            'outlet_id' => Outlet::factory()->publiclyVisible(),
            'event_type' => EngagementEventType::PlaceView,
            'anonymous_session_hmac' => hash('sha256', fake()->uuid()),
            'metadata' => [],
            'occurred_at' => now(),
            'received_at' => now(),
            'request_id' => (string) Str::uuid(),
        ];
    }
}
