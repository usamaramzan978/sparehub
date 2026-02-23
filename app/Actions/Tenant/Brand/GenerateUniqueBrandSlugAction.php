<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Brand;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class GenerateUniqueBrandSlugAction
{
    public function handle(string $name, ?string $ignoreBrandId = null): string
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
