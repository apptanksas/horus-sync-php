<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Trait\EnumIterator;

enum SyncFileStatus: int
{
    use EnumIterator;
    /**
     * The file upload is pending to linked with a reference.
     */
    case PENDING = 0;
    /**
     * The file upload is linked with a reference.
     */
    case LINKED = 1;
}