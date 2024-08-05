<?php

namespace AppTank\Horus\Core;

use AppTank\Horus\Core\Trait\EnumList;

enum SyncAction
{
    use EnumList;

    case READ;
    case INSERT;
    case UPDATE;
    case DELETE;
}
