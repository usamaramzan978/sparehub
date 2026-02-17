<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case TENANT_OWNER = 'tenant-owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case CASHIER = 'cashier';
}
