<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseReturnStatus;
use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PurchaseReturn extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'vendor_id',
        'purchase_id',
        'created_by',
        'return_no',
        'return_date',
        'status',
        'grand_total',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'grand_total' => 'decimal:2',
        'status' => PurchaseReturnStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
