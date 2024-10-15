<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\File\SyncFileStatus;

readonly class FileUploaded
{
    function __construct(
        public string         $id,
        public string         $mimeType,
        public string         $path,
        public string         $publicUrl,
        public string|int     $ownerId,
        public SyncFileStatus $status = SyncFileStatus::PENDING,
    )
    {

    }
}