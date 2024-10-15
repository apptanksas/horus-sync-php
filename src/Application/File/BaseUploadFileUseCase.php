<?php

namespace AppTank\Horus\Application\File;

use AppTank\Horus\Core\Model\FileUploaded;

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