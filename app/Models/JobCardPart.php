<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JobCardPart extends Model
{
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'job_card_id',
        'product_id',
        'qty',
        'cost',
        'mrp',
        'retail_price',
        'wholesale_price',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'cost' => 'decimal:2',
        'mrp' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
