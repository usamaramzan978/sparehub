<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $categories = Category::query()
            ->with('parent')
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

    public function store(CategoryRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['slug'] = $this->buildUniqueSlug((string) $payload['name']);

        Category::query()->create($payload);

        return to_route('tenant.categories.index')
            ->with('status', 'Created.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $payload = $request->validated();
        $payload['slug'] = $this->buildUniqueSlug((string) $payload['name'], $category->id);

        $category->update($payload);

        return to_route('tenant.categories.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return to_route('tenant.categories.index')
            ->with('status', 'Deleted.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->status = $category->status === RecordStatus::ACTIVE
            ? RecordStatus::INACTIVE
            : RecordStatus::ACTIVE;
        $category->save();

        return back()->with('status', 'Status updated.');
    }

    private function buildUniqueSlug(string $name, ?string $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'category';

        $candidateSlug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($candidateSlug, $ignoreCategoryId)) {
            $candidateSlug = sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        }

        return $candidateSlug;
    }

    private function slugExists(string $slug, ?string $ignoreCategoryId = null): bool
    {
        return Category::query()
            ->where('slug', $slug)
            ->when($ignoreCategoryId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreCategoryId))
            ->exists();
    }
}
