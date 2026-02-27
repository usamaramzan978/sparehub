<?php

declare(strict_types=1);

namespace App\Enums;

enum CommissionType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
}
