<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Unit;

use App\Models\Unit;

final class UpdateUnitAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Unit $unit, array $data): bool
    {
        return $unit->update($data);
    }
}
