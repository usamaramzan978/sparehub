<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantSetting extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'company_name',
        'logo_path',
        'support_email',
        'support_phone',
        'enable_two_factor',
        'notify_email',
        'enable_otp',
        'otp_length',
        'otp_expiry_minutes',
    ];

    protected $casts = [
        'enable_two_factor' => 'bool',
        'notify_email' => 'bool',
        'enable_otp' => 'bool',
        'otp_length' => 'int',
        'otp_expiry_minutes' => 'int',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
