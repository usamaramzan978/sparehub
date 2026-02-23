<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Tax;

use App\Models\Tax;

final class UpdateTaxAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Tax $tax, array $data): bool
    {
        return $tax->update($data);
    }
}
