<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Brand;

use App\Models\Brand;

final class DeleteBrandAction
{
    public function handle(Brand $brand): bool
    {
        return (bool) $brand->delete();
    }
}
