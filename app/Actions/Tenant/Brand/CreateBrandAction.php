<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Brand;

use App\Models\Brand;

final readonly class CreateBrandAction
{
    public function __construct(private GenerateUniqueBrandSlugAction $slugAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): Brand
    {
        $payload['slug'] = $this->slugAction->handle((string) $payload['name']);

        return Brand::query()->create($payload);
    }
}
