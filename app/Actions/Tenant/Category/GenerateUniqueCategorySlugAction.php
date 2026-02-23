<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Category;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class GenerateUniqueCategorySlugAction
{
    public function handle(string $name, ?string $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'category';

        $candidateSlug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($candidateSlug, $ignoreCategoryId)) {
            $candidateSlug = sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        }

        return $candidateSlug;
    }

    private function slugExists(string $slug, ?string $ignoreCategoryId = null): bool
    {
        return Category::query()
            ->where('slug', $slug)
            ->when($ignoreCategoryId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreCategoryId))
            ->exists();
    }
}
