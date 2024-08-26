<?php

namespace AppTank\Horus\Core\Auth;

use AppTank\Horus\Core\Trait\EnumIterator;

enum Permission: string
{
    use EnumIterator;

    case READ = "R";
    case CREATE = "C";
    case UPDATE = "U";
    case DELETE = "D";
}