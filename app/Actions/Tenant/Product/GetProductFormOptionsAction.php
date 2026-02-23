<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Enums\RecordStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Tax;
use App\Models\Unit;

final class GetProductFormOptionsAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'taxes' => Tax::query()
                ->where('status', RecordStatus::ACTIVE->value)
                ->orderBy('name')
                ->get(),
            'units' => Unit::query()
                ->where('status', RecordStatus::ACTIVE->value)
                ->orderBy('name')
                ->get(),
            'statuses' => RecordStatus::cases(),
        ];
    }
}
