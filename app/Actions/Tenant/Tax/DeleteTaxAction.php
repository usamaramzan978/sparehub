<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Tax;

use App\Models\Tax;

final class DeleteTaxAction
{
    public function handle(Tax $tax): bool
    {
        return (bool) $tax->delete();
    }
}
