<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Branch;

use App\Enums\BranchDeletionResult;
use App\Models\Branch;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class DeleteBranchAction
{
    public function handle(Branch $branch, mixed $currentBranchId): BranchDeletionResult
    {
        if (Branch::query()->count() <= 1) {
            return BranchDeletionResult::LastRemaining;
        }

        if ($currentBranchId === $branch->id) {
            return BranchDeletionResult::CurrentSelected;
        }

        $branchSnapshot = [
            'branch_id' => (string) $branch->id,
            'branch_name' => $branch->name,
        ];

        $branch->delete();

        AuditTimelineLogger::log(
            event: 'branch_deleted',
            description: 'Branch deleted.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: $branchSnapshot,
        );

        return BranchDeletionResult::Deleted;
    }
}
