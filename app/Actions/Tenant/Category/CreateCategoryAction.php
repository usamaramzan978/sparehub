<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Category;

use App\Models\Category;

final readonly class CreateCategoryAction
{
    public function __construct(private GenerateUniqueCategorySlugAction $slugAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): Category
    {
        $payload['slug'] = $this->slugAction->handle((string) $payload['name']);

        return Category::query()->create($payload);
    }
}
