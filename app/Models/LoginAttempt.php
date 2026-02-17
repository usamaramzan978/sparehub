<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoginAttempt extends Model
{
    use HasFactory;
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'user_id',
        'user_type',
        'attempted_at',
    ];

    /**
     * Get the user associated with this login attempt (if exists).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * Scope to get recent failed attempts for an email.
     */
    #[Scope]
    protected function recentFailures($query, string $email, int $minutes = 60)
    {
        return $query->where('email', $email)
            ->where('status', 'failed')
            ->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to get attempts by IP address.
     */
    #[Scope]
    protected function byIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to get successful logins.
     */
    #[Scope]
    protected function successful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to get blocked attempts.
     */
    #[Scope]
    protected function blocked($query)
    {
        return $query->where('status', 'blocked');
    }
}
