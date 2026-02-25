<?php

declare(strict_types=1);

namespace App\Enums;

enum UserDeletionResult
{
    case LastTenantOwner;
    case Deleted;
}
