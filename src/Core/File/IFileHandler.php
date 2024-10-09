<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Model\FileUploaded;
use Illuminate\Http\UploadedFile;

interface IFileHandler
{
    /**
     * Uploads a file to the specified location.
     *
     * @param string|int $userOwnerId The ID of the user who owns the file.
     * @param string $fileId The ID of the file.
     * @param UploadedFile $file The file to upload.
     *
     * @return FileUploaded The file that was uploaded.
     */
    function upload(string|int $userOwnerId, string $fileId, UploadedFile $file): FileUploaded;

    /**
     * Deletes a file.
     *
     * @param FileUploaded $file
     * @return void
     */
    function delete(FileUploaded $file): void;
}