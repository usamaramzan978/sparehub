<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Brand\CreateBrandAction;
use App\Actions\Tenant\Brand\DeleteBrandAction;
use App\Actions\Tenant\Brand\UpdateBrandAction;
use App\Enums\BrandStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BrandRequest;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function store(BrandRequest $request, CreateBrandAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.brands.index')
            ->with('status', 'Created.');
    }

    public function update(BrandRequest $request, Brand $brand, UpdateBrandAction $action): RedirectResponse
    {
        $action->handle($brand, $request->validated());

        return to_route('tenant.brands.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Brand $brand, DeleteBrandAction $action): RedirectResponse
    {
        $action->handle($brand);

        return to_route('tenant.brands.index')
            ->with('status', 'Deleted.');
    }
}
