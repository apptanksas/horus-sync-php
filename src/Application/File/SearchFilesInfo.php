<?php

namespace AppTank\Horus\Application\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\FileUploadedRepository;

/**
 * @internal Class SearchFiles
 *
 * This use case searches for files in batch by their reference IDs and returns an array of file URLs, MIME types, and
 * statuses. It uses the `FileUploadedRepository` to search for the files and retrieve their information.
 *
 * @package AppTank\Horus\Application\File
 */
readonly class SearchFilesInfo
{
    public function __construct(
        private FileUploadedRepository $fileUploadedRepository,
    )
    {

    }

    /**
     * Searches for files in batch by their reference IDs and returns an array of file URLs, MIME types, and statuses.
     *
     * @param UserAuth $userAuth The authenticated user performing the search.
     * @param string[] $ids The reference IDs of the files to search for.
     * @return array An array of file URLs, MIME types, and statuses.
     */
    function __invoke(UserAuth $userAuth, array $ids): array
    {
        $output = [];
        $files = $this->fileUploadedRepository->searchInBatch($userAuth->getEffectiveUserId(), $ids);

        foreach ($files as $file) {
            $output[] = [
                "id" => $file->id,
                "url" => $file->publicUrl,
                "mime_type" => $file->mimeType,
                "status" => $file->status->value()
            ];
        }

        return $output;
    }

}