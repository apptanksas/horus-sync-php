<?php

namespace AppTank\Horus\Core;


use AppTank\Horus\Core\Trait\EnumIterator;

enum SyncAction: string
{
    use EnumIterator;

    case READ = "R";
    case INSERT = "I";
    case UPDATE = "U";
    case DELETE = "D";
}
