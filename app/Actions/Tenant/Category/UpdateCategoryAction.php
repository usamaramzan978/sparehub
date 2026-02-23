<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Category;

use App\Models\Category;

final readonly class UpdateCategoryAction
{
    public function __construct(private GenerateUniqueCategorySlugAction $slugAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Category $category, array $payload): bool
    {
        $payload['slug'] = $this->slugAction->handle((string) $payload['name'], (string) $category->id);

        return $category->update($payload);
    }
}
