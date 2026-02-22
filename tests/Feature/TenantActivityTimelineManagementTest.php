<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

beforeEach(function (): void {
    Config::set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    Config::set('database.default', 'tenant');
    Config::set('tenancy.database.central_connection', 'tenant');

    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);

    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    URL::defaults(['tenant' => 'test-tenant-id']);
});

function activityTimelineRoute(): string
{
    return route('tenant.activity-timeline.index', ['tenant' => 'test-tenant-id']);
}

it('shows recent activity entries from tenant timeline table', function (): void {
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Activity User',
        'email' => 'activity.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    DB::connection('tenant')->table('tenant_activity_timelines')->insert([
        [
            'description' => 'Tenant login attempt failed.',
            'event' => 'auth_failure',
            'properties' => json_encode(['email' => 'a@example.test'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'description' => 'Branch context switched.',
            'event' => 'branch_switched',
            'properties' => json_encode(['to_branch_name' => 'Main'], JSON_THROW_ON_ERROR),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ],
    ]);

    $this->actingAs($user, 'user');
    $this->withSession(['tenant.current_branch_id' => $branch->id]);

    $response = $this->get(activityTimelineRoute());

    $response->assertSuccessful();
    $response->assertSee('Recent Activity');
    $response->assertSee('Failed login attempt');
    $response->assertSee('Branch switched to Main');
});
