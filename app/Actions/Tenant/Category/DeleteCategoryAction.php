<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Category;

use App\Models\Category;

final class DeleteCategoryAction
{
    public function handle(Category $category): bool
    {
        return (bool) $category->delete();
    }
}
