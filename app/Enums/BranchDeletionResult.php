<?php

declare(strict_types=1);

namespace App\Enums;

enum BranchDeletionResult
{
    case LastRemaining;
    case CurrentSelected;
    case Deleted;
}
