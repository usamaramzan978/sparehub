<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleStatus: string
{
    case DRAFT = 'draft';
    case HOLD = 'hold';
    case POSTED = 'posted';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';
}
