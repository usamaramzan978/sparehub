<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CustomerVehicle extends Model
{
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'customer_id',
        'registration_no',
        'model',
        'year',
        'chassis_no',
        'engine_no',
        'meter_reading',
    ];

    protected $casts = [
        'year' => 'int',
        'meter_reading' => 'decimal:3',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'vehicle_id');
    }
}
