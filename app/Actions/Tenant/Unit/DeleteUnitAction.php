<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Unit;

use App\Models\Unit;

final class DeleteUnitAction
{
    public function handle(Unit $unit): bool
    {
        return (bool) $unit->delete();
    }
}
