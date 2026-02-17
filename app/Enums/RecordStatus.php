<?php

declare(strict_types=1);

namespace App\Enums;

enum RecordStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
