<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Category\CreateCategoryAction;
use App\Actions\Tenant\Category\DeleteCategoryAction;
use App\Actions\Tenant\Category\UpdateCategoryAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $categories = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
        $parents = Category::query()->orderBy('name')->get();
        $statuses = RecordStatus::cases();

        return view('tenants.categories.index', [
            'items' => $categories,
            'parents' => $parents,
            'statuses' => $statuses,
        ]);
    }

    public function store(CategoryRequest $request, CreateCategoryAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.categories.index')
            ->with('status', 'Created.');
    }

    public function update(CategoryRequest $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $action->handle($category, $request->validated());

        return to_route('tenant.categories.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Category $category, DeleteCategoryAction $action): RedirectResponse
    {
        $action->handle($category);

        return to_route('tenant.categories.index')
            ->with('status', 'Deleted.');
    }
}
