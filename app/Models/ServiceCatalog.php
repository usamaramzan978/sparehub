<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServiceCatalog extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'service_catalog';

    protected $fillable = [
        'branch_id',
        'default_tax_id',
        'code',
        'name',
        'category',
        'base_price',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'duration_minutes' => 'int',
        'status' => RecordStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    protected function getIsTaxableAttribute(): bool
    {
        return $this->default_tax_id !== null;
    }
}
