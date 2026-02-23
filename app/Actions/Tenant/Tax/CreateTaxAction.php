<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Tax;

use App\Models\Tax;

final class CreateTaxAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Tax
    {
        return Tax::query()->create($data);
    }
}
