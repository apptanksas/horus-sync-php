<?php

namespace AppTank\Horus\Domain;

use AppTank\Horus\Domain\Trait\EnumList;

enum SyncAction
{
    use EnumList;

    case READ;
    case INSERT;
    case UPDATE;
    case DELETE;
}
