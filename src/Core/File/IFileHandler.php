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
     * @param string $path
     * @param UploadedFile $file The file to upload.
     *
     * @return FileUploaded The file that was uploaded.
     */
    function upload(string|int $userOwnerId, string $fileId, string $path, UploadedFile $file): FileUploaded;

    /**
     * Deletes a file.
     *
     * @param string $pathFile The path to the file to delete.
     * @return bool True if the file was deleted successfully; otherwise, false.
     */
    function delete(string $pathFile): bool;

    /**
     * Gets the MIME types allowed for file uploads.
     *
     * @return array
     */
    function getMimeTypesAllowed(): array;

    /**
     * Copies a file from one location to another.
     *
     * @param string $pathTo The path to copy the file to.
     * @param string $pathFrom The path to copy the file from.
     * @return bool True if the file was copied successfully; otherwise, false.
     */
    function copy(string $pathTo, string $pathFrom): bool;


    /**
     * Generates a URL for a file.
     *
     * @param string $path The path to the file.
     * @return string The URL for the file.
     */
    function generateUrl(string $path): string;
}