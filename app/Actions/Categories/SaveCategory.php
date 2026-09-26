<?php

namespace App\Actions\Categories;

use App\Data\Forms\CategoryData;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin adds or renames a category. The slug is made once from the first name and never changes.
 */
class SaveCategory
{
    /**
     * @throws ValidationException
     */
    public function create(CategoryData $data): Category
    {
        $slug = Category::slugFor($data->name);

        if ($slug === '') {
            throw ValidationException::withMessages(['name' => __('The name must contain a letter or a digit.')]);
        }

        if (Category::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => __('A category with this name already exists.')]);
        }

        $category = new Category([
            ...$data->toModelAttributes(),
            'position' => (int) Category::query()->max('position') + 1,
        ]);
        $category->slug = $slug;
        $category->save();

        return $category;
    }

    public function update(Category $category, CategoryData $data): Category
    {
        $category->update($data->toModelAttributes());

        return $category;
    }

    /**
     * Put the categories in the given order. Categories left out keep their place after them.
     *
     * @param  list<string>  $categoryIds
     */
    public function reorder(array $categoryIds): void
    {
        DB::transaction(function () use ($categoryIds): void {
            foreach (array_values(array_unique($categoryIds)) as $position => $categoryId) {
                Category::query()->whereKey($categoryId)->update(['position' => $position + 1]);
            }
        });
    }
}
