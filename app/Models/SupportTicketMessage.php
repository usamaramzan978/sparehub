<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupportTicketMessage extends Model
{
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    public const SENDER_TENANT = 'tenant';

    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'support_ticket_id',
        'sender_type',
        'sender_user_id',
        'sender_name',
        'message',
    ];

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }
}
