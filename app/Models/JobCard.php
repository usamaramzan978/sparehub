<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobCardStatus;
use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JobCard extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'vehicle_id',
        'assigned_employee_id',
        'created_by',
        'job_no',
        'job_date',
        'status',
        'meter_reading',
        'next_reading',
        'total_visits',
        'remarks',
        'in_time',
        'out_time',
    ];

    protected $casts = [
        'job_date' => 'date',
        'meter_reading' => 'decimal:3',
        'next_reading' => 'decimal:3',
        'total_visits' => 'int',
        'in_time' => 'datetime',
        'out_time' => 'datetime',
        'status' => JobCardStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(JobCardService::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(JobCardPart::class);
    }
}
