<?php

namespace AppTank\Horus\Application\File;

use AppTank\Horus\Core\Exception\FileNotFoundException;
use AppTank\Horus\Core\Repository\FileUploadedRepository;


/**
 * @internal Class SearchFileInfo
 *
 * Represents an application service for searching a file info. This class defines a method for searching a file info
 * by its reference ID.
 *
 * @package AppTank\Horus\Application\File
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class SearchFileInfo extends BaseUploadFileUseCase
{
    /**
     * Constructor for the SearchFileInfo class.
     *
     * @param FileUploadedRepository $fileUploadedRepository The repository for file uploaded entities.
     */
    public function __construct(
        private FileUploadedRepository $fileUploadedRepository,
    )
    {

    }


    /**
     * Invokes the SearchFileUrl class to search for a file URL by its reference ID.
     *
     * @param string $fileId The reference ID of the file to search.
     * @return array The URL and MIME type of the file.
     *
     * @throws FileNotFoundException If the file is not found.
     */
    function __invoke(string $fileId): array
    {
        $fileUploaded = $this->fileUploadedRepository->search($fileId);

        if (is_null($fileUploaded)) {
            throw new FileNotFoundException("File with reference id [$fileId] not found");
        }

        return $this->parseFileUploaded($fileUploaded);
    }
}