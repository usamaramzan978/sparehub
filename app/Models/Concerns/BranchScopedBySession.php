<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BranchScopedBySession
{
    public static function bootBranchScopedBySession(): void
    {
        static::addGlobalScope('session_branch', function (Builder $builder): void {
            if (! self::shouldApplySessionBranchScope()) {
                return;
            }

            $branchId = session('tenant.current_branch_id');
            if (! $branchId) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.branch_id', $branchId);
        });

        static::saving(function (Model $model): void {
            if (! self::shouldApplySessionBranchScope()) {
                return;
            }

            $branchId = session('tenant.current_branch_id');
            if (
                $branchId
                && $model->isFillable('branch_id')
                && ! $model->getAttribute('branch_id')
            ) {
                $model->setAttribute('branch_id', $branchId);
            }
        });
    }

    private static function shouldApplySessionBranchScope(): bool
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            return false;
        }

        $route = request()->route();

        return $route !== null && request()->routeIs('tenant.*');
    }
}
