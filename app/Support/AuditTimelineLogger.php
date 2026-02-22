<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Facades\Tenancy;

final class AuditTimelineLogger
{
    private const TABLE = 'tenant_activity_timelines';

    private const TENANT_CONNECTION = 'tenant';

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $event,
        string $description,
        ?Model $causer = null,
        ?Model $subject = null,
        array $properties = [],
    ): void {
        rescue(fn () => self::persist($event, $description, $causer, $subject, $properties), null, false);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function logForTenant(
        string $tenantId,
        string $event,
        string $description,
        ?Model $causer = null,
        ?Model $subject = null,
        array $properties = [],
    ): void {
        rescue(function () use ($tenantId, $event, $description, $causer, $subject, $properties): void {
            if (self::canPersistOnCurrentConnection()
                && (! tenancy()->initialized || (string) tenant('id') === $tenantId)) {
                self::persist($event, $description, $causer, $subject, $properties);

                return;
            }

            if (tenancy()->initialized && (string) tenant('id') === $tenantId) {
                self::persist($event, $description, $causer, $subject, $properties);

                return;
            }

            $tenant = Tenant::query()->find($tenantId);
            if (! $tenant) {
                return;
            }

            $originalTenant = tenancy()->initialized ? tenant() : null;
            $mustInitialize = ! tenancy()->initialized || (string) tenant('id') !== $tenantId;
            $initializedByLogger = false;

            try {
                if ($mustInitialize) {
                    Tenancy::initialize($tenant);
                    $initializedByLogger = true;
                }

                self::persist($event, $description, $causer, $subject, $properties);
            } finally {
                if ($initializedByLogger) {
                    Tenancy::end();

                    if ($originalTenant) {
                        Tenancy::initialize($originalTenant);
                    }
                }
            }
        }, null, true);
    }

    private static function canPersistOnCurrentConnection(): bool
    {
        $connection = config('database.default');

        return is_string($connection)
            && $connection !== ''
            && Schema::connection($connection)->hasTable(self::TABLE);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private static function persist(
        string $event,
        string $description,
        ?Model $causer,
        ?Model $subject,
        array $properties
    ): void {
        $connection = tenancy()->initialized
            ? self::TENANT_CONNECTION
            : config('database.default');

        if (! is_string($connection) || $connection === '' || ! Schema::connection($connection)->hasTable(self::TABLE)) {
            return;
        }

        $request = request();
        $contextProperties = [
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ];

        DB::connection($connection)->table(self::TABLE)->insert([
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'causer_type' => $causer ? $causer::class : null,
            'causer_id' => $causer?->getKey(),
            'branch_id' => session('tenant.current_branch_id'),
            'properties' => json_encode(array_merge($contextProperties, $properties), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
