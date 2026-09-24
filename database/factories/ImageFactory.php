<?php

namespace Database\Factories;

use App\Enums\ImageKind;
use App\Models\Image;
use App\Models\Outlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imageable_type' => 'outlet',
            'imageable_id' => Outlet::factory(),
            'kind' => ImageKind::Gallery,
            'disk' => 'public',
            'path' => 'outlet/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(4),
            'width' => 1200,
            'height' => 800,
            'position' => 1,
        ];
    }

    /**
     * Indicate that the image was removed, long enough ago that its file can be pruned.
     */
    public function removed(int $daysAgo = 31): static
    {
        return $this->state(fn (array $attributes) => [
            'removed_at' => now()->subDays($daysAgo),
        ]);
    }
}
