<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxType: string
{
    case SALE = 'sale';
    case PURCHASE = 'purchase';
}
