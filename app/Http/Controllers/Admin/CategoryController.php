<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Categories\SaveCategory;
use App\Data\Admin\CategoryData;
use App\Data\Forms\CategoryData as CategoryFormData;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The category list owners pick from for their outlets.
 */
class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/categories/index', [
            'categories' => CategoryData::collect(Category::query()->withCount('outlets')->ordered()->get()),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(CategoryFormData $data, SaveCategory $saveCategory): RedirectResponse
    {
        $saveCategory->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category added.')]);

        return back();
    }

    public function update(Category $category, CategoryFormData $data, SaveCategory $saveCategory): RedirectResponse
    {
        $saveCategory->update($category, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category saved.')]);

        return back();
    }

    public function reorder(Request $request, SaveCategory $saveCategory): RedirectResponse
    {
        /** @var array{ids: list<string>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $saveCategory->reorder($validated['ids']);

        return back();
    }
}
