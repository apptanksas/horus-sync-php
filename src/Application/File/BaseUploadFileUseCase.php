<?php

namespace AppTank\Horus\Application\File;

use AppTank\Horus\Core\Model\FileUploaded;

/**
 * @internal Class BaseUploadFileUseCase
 *
 * Base class for use cases that upload files.
 *
 * @package AppTank\Horus\Application\File
 *
 * @author John Ospina
 * Year: 2024
 */
abstract readonly class BaseUploadFileUseCase
{
    protected function parseFileUploaded(FileUploaded $fileUploaded): array
    {
        return [
            "id" => $fileUploaded->id,
            "url" => $fileUploaded->publicUrl,
            "mime_type" => $fileUploaded->mimeType,
            "status" => $fileUploaded->status->value
        ];
    }
}