<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethodType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case CARD = 'card';
    case WALLET = 'wallet';
    case OTHER = 'other';
}
