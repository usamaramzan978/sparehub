<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\SupportTicket\CreateSupportTicketAction;
use App\Actions\Tenant\SupportTicket\EnsureSupportTicketInBranchAction;
use App\Actions\Tenant\SupportTicket\ResolveSupportTicketAction;
use App\Actions\Tenant\SupportTicket\StoreSupportTicketMessageAction;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SupportTicketMessageRequest;
use App\Http\Requests\Tenant\SupportTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function store(SupportTicketRequest $request, CreateSupportTicketAction $createSupportTicketAction): RedirectResponse
    {
        $ticket = $createSupportTicketAction->handle($request->validated(), $this->currentBranchId(), $request->file('images', []));

        return to_route('tenant.support-tickets.show', $ticket)->with('status', 'Created.');
    }

    public function show(ResolveSupportTicketAction $resolveSupportTicketAction, EnsureSupportTicketInBranchAction $ensureSupportTicketInBranchAction): View
    {
        $supportTicket = $resolveSupportTicketAction->handle((string) request()->route('supportTicket'));
        $supportTicket = $ensureSupportTicketInBranchAction->handle($supportTicket, $this->currentBranchId());

        $supportTicket->load(['reporter', 'messages']);

        return view('tenants.support-tickets.show', [
            'supportTicket' => $supportTicket,
        ]);
    }

    public function storeMessage(
        SupportTicketMessageRequest $request,
        ResolveSupportTicketAction $resolveSupportTicketAction,
        EnsureSupportTicketInBranchAction $ensureSupportTicketInBranchAction,
        StoreSupportTicketMessageAction $storeSupportTicketMessageAction
    ): RedirectResponse {
        $supportTicket = $resolveSupportTicketAction->handle((string) request()->route('supportTicket'));
        $supportTicket = $ensureSupportTicketInBranchAction->handle($supportTicket, $this->currentBranchId());

        $storeSupportTicketMessageAction->handle($supportTicket, (string) $request->validated('message'));

        return to_route('tenant.support-tickets.show', $supportTicket)->with('status', 'Message sent.');
    }
}
