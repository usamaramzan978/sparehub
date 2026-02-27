<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceType;
use App\Enums\SaleStatus;
use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Sale extends Model implements HasMedia
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'job_card_id',
        'created_by',
        'invoice_no',
        'invoice_date',
        'status',
        'invoice_type',
        'sub_total',
        'discount_total',
        'tax_total',
        'grand_total',
        'paid_total',
        'balance_due',
        'notes',
        'posted_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'posted_at' => 'datetime',
        'status' => SaleStatus::class,
        'invoice_type' => InvoiceType::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('online_payment_proof')->singleFile();
    }
}
