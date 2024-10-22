<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Trait\EnumIterator;

/**
 * @internal Class SyncFileStatus
 *
 * Enumerates the possible statuses for a file upload.
 *
 * @package AppTank\Horus\Core\File
 *
 * @author John Ospina
 * Year: 2024
 */
enum SyncFileStatus: string
{
    use EnumIterator;

    /**
     * The file upload is pending to linked with a reference.
     */
    case PENDING = '0';
    /**
     * The file upload is linked with a reference.
     */
    case LINKED = '1';

    /**
     * The file upload is deleted.
     */
    case DELETED = '2';
}