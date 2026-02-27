<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleReturnStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';
}
