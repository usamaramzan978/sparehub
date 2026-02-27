<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmployeeSalary extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'user_id',
        'salary_month',
        'per_day_salary',
        'working_days',
        'basic_salary',
        'bonus',
        'deduction',
        'net_salary',
        'paid_at',
        'notes',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'salary_month' => 'date',
            'per_day_salary' => 'decimal:2',
            'working_days' => 'integer',
            'basic_salary' => 'decimal:2',
            'bonus' => 'decimal:2',
            'deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
