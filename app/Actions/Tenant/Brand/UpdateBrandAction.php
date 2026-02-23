<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Brand;

use App\Models\Brand;

final readonly class UpdateBrandAction
{
    public function __construct(private GenerateUniqueBrandSlugAction $slugAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Brand $brand, array $payload): bool
    {
        $payload['slug'] = $this->slugAction->handle((string) $payload['name'], (string) $brand->id);

        return $brand->update($payload);
    }
}
