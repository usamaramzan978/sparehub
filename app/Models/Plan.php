<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'monthly_price',
        'annual_price',
        'max_users',
        'max_branches',
        'status',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'max_users' => 'integer',
            'max_branches' => 'integer',
            'status' => RecordStatus::class,
        ];
    }
}
