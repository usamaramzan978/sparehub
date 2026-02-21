<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TwoFactorMethod;
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
        'timezone',
        'two_factor_enabled',
        'two_factor_method',
        'email_notifications_enabled',
    ];

    protected $casts = [
        'two_factor_enabled' => 'bool',
        'two_factor_method' => TwoFactorMethod::class,
        'email_notifications_enabled' => 'bool',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
