<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Unit;

use App\Models\Unit;

final class CreateUnitAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Unit
    {
        return Unit::query()->create($data);
    }
}
