<?php

namespace AppTank\Horus\Application\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\FileUploadedRepository;

readonly class SearchFiles
{
    public function __construct(
        private FileUploadedRepository $fileUploadedRepository,
    )
    {

    }


    function __invoke(UserAuth $userAuth, array $ids): array
    {
        $output = [];
        $files = $this->fileUploadedRepository->searchInBatch($userAuth->getEffectiveUserId(), $ids);

        foreach ($files as $file) {
            $output[] = [
                "url" => $file->publicUrl,
                "mime_type" => $file->mimeType,
                "status" => $file->status->value()
            ];
        }

        return $output;
    }

}