<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Vendor;

use App\Models\Vendor;

final class DeleteVendorAction
{
    public function handle(Vendor $vendor): bool
    {
        return (bool) $vendor->delete();
    }
}
