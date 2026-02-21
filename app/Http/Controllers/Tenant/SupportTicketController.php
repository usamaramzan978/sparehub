<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SupportTicketMessageRequest;
use App\Http\Requests\Tenant\SupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $status = mb_trim($request->string('status')->toString());

        $tickets = SupportTicket::query()
            ->with('reporter')
            ->where('branch_id', $branchId)
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('ticket_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('title', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('description', 'like', sprintf('%%%s%%', $search));
                })
            )
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.support-tickets.index', [
            'items' => $tickets,
            'statuses' => SupportTicketStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('tenants.support-tickets.create');
    }

    public function store(SupportTicketRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $branchId = $this->currentBranchId();

        $ticket = SupportTicket::query()->create([
            'branch_id' => $branchId,
            'reported_by' => auth('user')->id(),
            'ticket_no' => $this->generateTicketNumber($branchId),
            'title' => $payload['title'],
            'description' => $payload['description'],
            'priority' => $payload['priority'] ?? SupportTicketPriority::MEDIUM->value,
            'status' => $payload['status'] ?? SupportTicketStatus::OPEN->value,
            'image_paths' => [],
        ]);

        $this->replaceTicketImages($ticket, $request->file('images', []));
        $this->createTenantMessage($ticket, $payload['description']);

        return to_route('tenant.support-tickets.show', $ticket)->with('status', 'Created.');
    }

    public function show(): View
    {
        $supportTicket = $this->resolveSupportTicket((string) request()->route('supportTicket'));
        $this->ensureSupportTicketInCurrentBranch($supportTicket);

        $supportTicket->load(['reporter', 'messages']);

        return view('tenants.support-tickets.show', [
            'supportTicket' => $supportTicket,
        ]);
    }

    public function storeMessage(SupportTicketMessageRequest $request): RedirectResponse
    {
        $supportTicket = $this->resolveSupportTicket((string) request()->route('supportTicket'));
        $this->ensureSupportTicketInCurrentBranch($supportTicket);
        $message = $request->validated('message');

        if (in_array($supportTicket->status->value, [SupportTicketStatus::RESOLVED->value, SupportTicketStatus::CLOSED->value], true)) {
            $supportTicket->update(['status' => SupportTicketStatus::OPEN->value]);
        }

        $this->createTenantMessage($supportTicket, $message);

        return to_route('tenant.support-tickets.show', $supportTicket)->with('status', 'Message sent.');
    }

    private function ensureSupportTicketInCurrentBranch(SupportTicket $supportTicket): void
    {
        abort_if($supportTicket->branch_id !== $this->currentBranchId(), 404);
    }

    private function resolveSupportTicket(string $supportTicketId): SupportTicket
    {
        return SupportTicket::query()->withoutGlobalScopes()->findOrFail($supportTicketId);
    }

    private function generateTicketNumber(string $branchId): string
    {
        do {
            $ticketNumber = sprintf('SUP-%s', Str::upper(Str::random(8)));
            $exists = SupportTicket::query()
                ->withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('ticket_no', $ticketNumber)
                ->exists();
        } while ($exists);

        return $ticketNumber;
    }

    private function createTenantMessage(SupportTicket $supportTicket, string $message): void
    {
        $user = auth('user')->user();

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $supportTicket->id,
            'sender_type' => SupportTicketMessage::SENDER_TENANT,
            'sender_user_id' => (string) ($user?->id ?? ''),
            'sender_name' => (string) ($user?->name ?? 'Tenant User'),
            'message' => $message,
        ]);
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $uploadedImages
     */
    private function replaceTicketImages(SupportTicket $supportTicket, array|UploadedFile|null $uploadedImages): void
    {
        $images = is_array($uploadedImages) ? $uploadedImages : [$uploadedImages];
        $images = array_values(array_filter($images, fn ($image): bool => $image instanceof UploadedFile));

        $this->deleteTicketImages($supportTicket);

        if ($images === []) {
            $supportTicket->update(['image_paths' => []]);

            return;
        }

        $storedPaths = [];
        foreach ($images as $image) {
            $storedPaths[] = (string) $image->store('support-tickets', 'public');
        }

        $supportTicket->update(['image_paths' => $storedPaths]);
    }

    private function deleteTicketImages(SupportTicket $supportTicket): void
    {
        $paths = $supportTicket->image_paths ?? [];

        if (! is_array($paths) || $paths === []) {
            return;
        }

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
