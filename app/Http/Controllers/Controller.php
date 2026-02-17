<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
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
}
