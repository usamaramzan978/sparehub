<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoginMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type_id',
        'type',
        'email',
        'password',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    public function getConnectionName(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default'));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
