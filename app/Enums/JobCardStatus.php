<?php

declare(strict_types=1);

namespace App\Enums;

enum JobCardStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case WAITING_PARTS = 'waiting_parts';
    case READY = 'ready';
    case DELIVERED = 'delivered';
    case INVOICED = 'invoiced';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';
}
