<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Product extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $fillable = [
        'category_id',
        'brand_id',
        'default_tax_id',
        'default_unit_id',
        'sku',
        'part_number',
        'barcode',
        'qrcode',
        'name',
        'description',
        'track_stock',
        'is_service_item',
        'status',
    ];

    protected $casts = [
        'track_stock' => 'bool',
        'is_service_item' => 'bool',
        'status' => RecordStatus::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
