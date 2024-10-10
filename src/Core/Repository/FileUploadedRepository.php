<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\FileUploaded;

/**
 * @internal Interface FileUploadedRepository
 *
 * Represents a repository for managing file uploads. This interface defines methods for saving, searching, and deleting
 * file uploads.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * @author John Ospina
 * Year: 2024
 */
interface FileUploadedRepository
{
    /**
     * Saves a file upload to the repository.
     *
     * @param FileUploaded $file The file upload to be saved.
     * @return void
     */
    function save(FileUploaded $file): void;

    /**
     * Searches for a file upload by its ID.
     *
     * @param string $id The ID of the file upload to search for.
     * @return FileUploaded|null The file upload with the specified ID, or null if no file is found.
     */
    function search(string $id): ?FileUploaded;

    /**
     * Deletes a file upload by its ID.
     *
     * @param string $id The ID of the file upload to delete.
     * @return void
     */
    function delete(string $id): void;
}