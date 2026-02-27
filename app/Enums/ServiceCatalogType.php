<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceCatalogType: string
{
    case Workshop = 'workshop';
    case Labour = 'labour';
}
