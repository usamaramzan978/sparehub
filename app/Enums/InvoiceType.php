<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';
    case MIXED = 'mixed';
}
