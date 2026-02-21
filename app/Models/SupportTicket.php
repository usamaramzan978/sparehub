<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\Concerns\BranchScopedBySession;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupportTicket extends Model
{
    use BranchScopedBySession;
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $fillable = [
        'branch_id',
        'reported_by',
        'ticket_no',
        'title',
        'description',
        'priority',
        'status',
        'system_reply',
        'replied_at',
        'replied_by_system_user_id',
        'replied_by_system_user_name',
        'image_paths',
    ];

    protected $casts = [
        'priority' => SupportTicketPriority::class,
        'status' => SupportTicketStatus::class,
        'replied_at' => 'datetime',
        'image_paths' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
