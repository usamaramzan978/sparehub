<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
    Storage::fake('public');
});

function supportTicketsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function supportTicketsTenantPath(string $suffix): string
{
    return '/firm/test-tenant-id/'.$suffix;
}

/**
 * @return array{current: Branch, secondary: Branch, user: User}
 */
function authenticateSupportTicketUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alternate Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Support User',
        'email' => 'support.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'user' => $user,
    ];
}

it('shows support tickets index for current branch only', function (): void {
    $fixture = authenticateSupportTicketUser();

    SupportTicket::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'reported_by' => $fixture['user']->id,
        'ticket_no' => 'SUP-CURR-1',
        'title' => 'Current branch ticket',
        'description' => 'Current branch issue.',
        'priority' => SupportTicketPriority::MEDIUM->value,
        'status' => SupportTicketStatus::OPEN->value,
    ]);

    SupportTicket::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'reported_by' => $fixture['user']->id,
        'ticket_no' => 'SUP-ALT-1',
        'title' => 'Other branch ticket',
        'description' => 'Must stay hidden.',
        'priority' => SupportTicketPriority::LOW->value,
        'status' => SupportTicketStatus::OPEN->value,
    ]);

    $response = $this->get(supportTicketsTenantRoute('support-tickets.index'));

    $response->assertSuccessful();
    $response->assertSee('Current branch ticket');
    $response->assertDontSee('Other branch ticket');
});

it('stores support ticket and creates first conversation message', function (): void {
    $fixture = authenticateSupportTicketUser();

    $response = $this->post(supportTicketsTenantRoute('support-tickets.store'), [
        'title' => 'POS crash while closing invoice',
        'description' => 'Error appears after submit button click.',
        'images' => [
            UploadedFile::fake()->image('proof-1.png'),
            UploadedFile::fake()->image('proof-2.png'),
            UploadedFile::fake()->image('proof-3.png'),
        ],
    ]);

    $ticket = SupportTicket::query()->withoutGlobalScopes()->first();
    expect($ticket)->not->toBeNull();

    $response->assertRedirect(supportTicketsTenantRoute('support-tickets.show', ['supportTicket' => $ticket]));
    $response->assertSessionHas('status', 'Created.');

    expect($ticket?->branch_id)->toBe($fixture['current']->id);
    expect($ticket?->priority->value)->toBe(SupportTicketPriority::MEDIUM->value);
    expect($ticket?->status->value)->toBe(SupportTicketStatus::OPEN->value);
    expect($ticket?->image_paths)->toBeArray()->toHaveCount(3);

    foreach ($ticket?->image_paths ?? [] as $storedImagePath) {
        Storage::disk('public')->assertExists($storedImagePath);
    }

    $message = SupportTicketMessage::query()->where('support_ticket_id', $ticket?->id)->first();
    expect($message)->not->toBeNull();
    expect($message?->sender_type)->toBe(SupportTicketMessage::SENDER_TENANT);
    expect($message?->message)->toBe('Error appears after submit button click.');
});

it('validates png-only image attachments', function (): void {
    authenticateSupportTicketUser();

    $response = $this->from(supportTicketsTenantRoute('support-tickets.create'))
        ->post(supportTicketsTenantRoute('support-tickets.store'), [
            'title' => 'Upload validation',
            'description' => 'Invalid extension test',
            'images' => [
                UploadedFile::fake()->image('invalid.jpg'),
            ],
        ]);

    $response->assertRedirect(supportTicketsTenantRoute('support-tickets.create'));
    $response->assertSessionHasErrors(['images.0']);
});

it('validates maximum of three image attachments', function (): void {
    authenticateSupportTicketUser();

    $response = $this->from(supportTicketsTenantRoute('support-tickets.create'))
        ->post(supportTicketsTenantRoute('support-tickets.store'), [
            'title' => 'Image count validation',
            'description' => 'Too many image files test',
            'images' => [
                UploadedFile::fake()->image('1.png'),
                UploadedFile::fake()->image('2.png'),
                UploadedFile::fake()->image('3.png'),
                UploadedFile::fake()->image('4.png'),
            ],
        ]);

    $response->assertRedirect(supportTicketsTenantRoute('support-tickets.create'));
    $response->assertSessionHasErrors(['images']);
});

it('does not allow tenant to open edit page or update/delete a ticket', function (): void {
    $fixture = authenticateSupportTicketUser();

    $ticket = SupportTicket::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'reported_by' => $fixture['user']->id,
        'ticket_no' => 'SUP-LOCK-1',
        'title' => 'Locked ticket',
        'description' => 'Ticket should not be editable by tenant.',
        'priority' => SupportTicketPriority::MEDIUM->value,
        'status' => SupportTicketStatus::OPEN->value,
    ]);

    $this->get(supportTicketsTenantPath('support-tickets/'.$ticket->id.'/edit'))->assertNotFound();
    $this->put(supportTicketsTenantPath('support-tickets/'.$ticket->id), [])->assertMethodNotAllowed();
    $this->delete(supportTicketsTenantPath('support-tickets/'.$ticket->id))->assertMethodNotAllowed();
});

it('allows tenant to reply and reopens resolved ticket', function (): void {
    $fixture = authenticateSupportTicketUser();

    $ticket = SupportTicket::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'reported_by' => $fixture['user']->id,
        'ticket_no' => 'SUP-REOPEN-1',
        'title' => 'Resolved ticket',
        'description' => 'Old resolved issue',
        'priority' => SupportTicketPriority::LOW->value,
        'status' => SupportTicketStatus::RESOLVED->value,
    ]);

    SupportTicketMessage::query()->create([
        'support_ticket_id' => $ticket->id,
        'sender_type' => SupportTicketMessage::SENDER_SYSTEM,
        'sender_user_id' => 'system-1',
        'sender_name' => 'System User',
        'message' => 'Issue marked as resolved.',
    ]);

    $response = $this->post(
        supportTicketsTenantRoute('support-tickets.messages.store', ['supportTicket' => $ticket]),
        ['message' => 'Problem still persists after check.']
    );

    $response->assertRedirect(supportTicketsTenantRoute('support-tickets.show', ['supportTicket' => $ticket]));
    $response->assertSessionHas('status', 'Message sent.');

    $ticket->refresh();
    expect($ticket->status->value)->toBe(SupportTicketStatus::OPEN->value);

    $latestMessage = SupportTicketMessage::query()
        ->where('support_ticket_id', $ticket->id)
        ->latest()
        ->first();

    expect($latestMessage?->sender_type)->toBe(SupportTicketMessage::SENDER_TENANT);
    expect($latestMessage?->message)->toBe('Problem still persists after check.');
});

it('returns not found when showing ticket outside current branch', function (): void {
    $fixture = authenticateSupportTicketUser();

    $foreignTicket = SupportTicket::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'reported_by' => $fixture['user']->id,
        'ticket_no' => 'SUP-ALT-404',
        'title' => 'Foreign ticket',
        'description' => 'Must return 404.',
        'priority' => SupportTicketPriority::LOW->value,
        'status' => SupportTicketStatus::OPEN->value,
    ]);

    $response = $this->get(supportTicketsTenantRoute('support-tickets.show', ['supportTicket' => $foreignTicket]));

    $response->assertNotFound();
});
