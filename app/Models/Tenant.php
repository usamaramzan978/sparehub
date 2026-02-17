<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'data',
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'data' => 'array',
    ];

    /**
     * Ensure these columns are stored as real columns, not in the data JSON.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'status',
            'data',
            'created_at',
            'updated_at',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }

    #[Scope]
    protected function inactive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::INACTIVE);
    }

    #[Scope]
    protected function suspended(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::SUSPENDED);
    }
}
