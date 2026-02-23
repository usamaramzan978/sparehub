<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Branch;

use App\Models\Branch;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class CreateBranchAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Branch
    {
        $branch = Branch::query()->create($data);

        AuditTimelineLogger::log(
            event: 'branch_created',
            description: 'Branch created.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
            ],
        );

        return $branch;
    }
}
