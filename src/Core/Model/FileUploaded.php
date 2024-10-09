<?php

namespace AppTank\Horus\Core\Model;

readonly class FileUploaded
{
    function __construct(
        public string     $id,
        public string     $mimeType,
        public string     $path,
        public string     $publicUrl,
        public string|int $ownerId,
    )
    {

    }
}