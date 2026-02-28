<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use App\Models\Concerns\UsesTenantConnection;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Notifiable;
    use UsesTenantConnection;

    // use CentralConnection;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'phone',
        'cnic',
        'image_path',
        'password',
        'status',
        'last_login_at',
        'last_login_ip',
        'two_factor_type',
        'two_factor_secret',
        'two_factor_verified_at',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedJobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'assigned_employee_id');
    }

    public function technicianServices(): HasMany
    {
        return $this->hasMany(JobCardService::class, 'technician_id');
    }

    public function createdSales(): HasMany
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    public function receivedSalePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'received_by');
    }

    public function createdSaleHolds(): HasMany
    {
        return $this->hasMany(SaleHold::class, 'created_by');
    }

    public function createdPurchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'created_by');
    }

    public function createdPurchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'created_by');
    }

    public function createdVendorPayments(): HasMany
    {
        return $this->hasMany(VendorPayment::class, 'created_by');
    }

    public function createdStockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class, 'created_by');
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(UserCommissionRule::class)->orderBy('sort_order');
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::SUSPENDED;
    }

    /**
     * Check if user has two-factor authentication enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_type !== null && $this->two_factor_secret !== null;
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_verified_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    #[Scope]
    protected function inactive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::INACTIVE);
    }

    #[Scope]
    protected function suspended(Builder $query): Builder
    {
        return $query->where('status', UserStatus::SUSPENDED);
    }
}
