<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\BrandStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BrandRequest;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $brands = Brand::query()
            ->withCount('products')
            ->when(
                mb_trim($request->string('search')->toString()) !== '',
                fn (Builder $query) => $query->where('name', 'like', '%'.mb_trim($request->string('search')->toString()).'%')
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $statuses = BrandStatus::cases();

        return view('tenants.brands.index', [
            'items' => $brands,
            'statuses' => $statuses,
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['slug'] = $this->buildUniqueSlug((string) $payload['name']);

        Brand::query()->create($payload);

        return to_route('tenant.brands.index')
            ->with('status', 'Created.');
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $payload = $request->validated();
        $payload['slug'] = $this->buildUniqueSlug((string) $payload['name'], $brand->id);

        $brand->update($payload);

        return to_route('tenant.brands.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return to_route('tenant.brands.index')
            ->with('status', 'Deleted.');
    }

    private function buildUniqueSlug(string $name, ?string $ignoreBrandId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'brand';

        $candidateSlug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($candidateSlug, $ignoreBrandId)) {
            $candidateSlug = sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        }

        return $candidateSlug;
    }

    private function slugExists(string $slug, ?string $ignoreBrandId = null): bool
    {
        return Brand::query()
            ->where('slug', $slug)
            ->when($ignoreBrandId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreBrandId))
            ->exists();
    }
}
