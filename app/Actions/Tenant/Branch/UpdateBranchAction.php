<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Branch;

use App\Models\Branch;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class UpdateBranchAction
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function handle(Branch $branch, array $changes): bool
    {
        $updated = $branch->update($changes);

        AuditTimelineLogger::log(
            event: 'branch_updated',
            description: 'Branch updated.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
                'changed_attributes' => array_keys($changes),
            ],
        );

        return $updated;
    }
}
