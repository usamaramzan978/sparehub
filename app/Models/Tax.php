<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Tax extends Model
{
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'code',
        'name',
        'rate',
        'is_inclusive',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_inclusive' => 'bool',
        'status' => RecordStatus::class,
    ];
}
