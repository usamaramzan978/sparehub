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

final class Warehouse extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => RecordStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
