<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionType;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserCommissionRule extends Model
{
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'user_id',
        'service_catalog_id',
        'total_amount',
        'commission_type',
        'commission_value',
        'payable_amount',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceCatalog(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'payable_amount' => 'decimal:2',
            'sort_order' => 'int',
            'commission_type' => CommissionType::class,
        ];
    }
}
