<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BrandStatus;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Brand extends Model
{
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'status' => BrandStatus::class,
        ];
    }
}
