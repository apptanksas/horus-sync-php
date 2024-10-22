<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\File\SyncFileStatus;

/**
 * @internal Class FileUploaded
 *
 * Represents a file that has been uploaded to the system. This class contains information about the file, such as its
 * MIME type, path, public URL, owner ID, and status.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class FileUploaded
{
    /**
     * Constructor for the FileUploaded class.
     *
     * @param string $id The reference ID of the file.
     * @param string $mimeType The MIME type of the file.
     * @param string $path The path to the file.
     * @param string $publicUrl The public URL of the file.
     * @param string|int $ownerId The ID of the file owner.
     * @param SyncFileStatus $status The status of the file. Defaults to `SyncFileStatus::PENDING`.
     */
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