<?php

namespace AppTank\Horus\Application\Upload;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Exception\UploadFileException;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use Illuminate\Http\UploadedFile;

/**
 * @internal Class UploadFile
 *
 * Represents an application service for uploading files. This class defines a method for uploading a file and saving it
 * to the repository.
 *
 * @package AppTank\Horus\Application\Upload
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class UploadFile
{
    public function __construct(
        private IFileHandler           $fileHandler,
        private FileUploadedRepository $fileUploadedRepository,
    )
    {

    }

    /**
     * @throws \Exception
     */
    function __invoke(UserAuth $userAuth, string $fileId, UploadedFile $file): array
    {
        $fileUploaded = null;

        try {
            $fileUploaded = $this->fileHandler->upload($userAuth->userId, $fileId, $file);
            $this->fileUploadedRepository->save($fileUploaded);
        } catch (\Exception $e) {
            if (!is_null($fileUploaded)) {
                $this->fileHandler->delete($fileUploaded);
            }
            throw new UploadFileException('Failed to upload file');
        }

        return ["url" => $fileUploaded->publicUrl];
    }
}