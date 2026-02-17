<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case PARTIAL_RECEIVED = 'partial_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
}
