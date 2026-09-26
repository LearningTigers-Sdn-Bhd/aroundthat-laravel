<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The starter categories. Safe to run again: existing categories are matched by slug and left as admins edited them.
 */
class CategorySeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const array NAMES = [
        'Restaurant',
        'Café',
        'Hawker & food court',
        'Bakery & desserts',
        'Bar & lounge',
        'Hotel & stay',
        'Shop',
        'Beauty & wellness',
        'Attraction',
        'Activity & tour',
        'Services',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::NAMES as $position => $name) {
            $category = Category::query()->firstOrNew(['slug' => Category::slugFor($name)]);

            if (! $category->exists) {
                $category->fill(['name' => $name, 'position' => $position + 1, 'is_active' => true])->save();
            }
        }
    }
}
