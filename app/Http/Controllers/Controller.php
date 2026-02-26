<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller
{
    protected function currentBranchId(): string
    {
        $branchId = session('tenant.current_branch_id');

        throw_if(! is_string($branchId) || $branchId === '', HttpException::class, 422, 'Please select a branch first.');

        return $branchId;
    }

    protected function ensureUserInCurrentBranch(User $user): void
    {
        abort_if($user->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @param  array<int, string>  $allowedSortColumns
     * @return array{0: ?string, 1: 'asc'|'desc'}
     */
    protected function resolveSort(Request $request, array $allowedSortColumns): array
    {
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();

        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        return [$activeSortBy, $activeSortDirection];
    }
}
