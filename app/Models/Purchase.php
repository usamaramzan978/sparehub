<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Purchase extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'vendor_id',
        'created_by',
        'purchase_no',
        'purchase_date',
        'due_date',
        'status',
        'grand_total',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'grand_total' => 'decimal:2',
        'status' => PurchaseStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }
}
