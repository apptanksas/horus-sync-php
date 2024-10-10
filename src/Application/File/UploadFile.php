<?php

namespace AppTank\Horus\Application\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
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
        private Config                 $config
    )
    {

    }

    /** Invokes the UploadFile class to upload a file and save it to the repository.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param string $fileId The ID of the file to upload.
     * @param UploadedFile $file The file to upload.
     * @return array The URL of the uploaded file.
     *
     * @throws UploadFileException If the file upload fails.
     */
    function __invoke(UserAuth $userAuth, string $fileId, UploadedFile $file): array
    {
        $fileUploaded = null;

        try {

            $mimeType = $file->getMimeType();
            $mimeTypesAllowed = $this->fileHandler->getMimeTypesAllowed();

            if (!in_array($mimeType, $mimeTypesAllowed)) {
                throw new UploadFileException("Invalid file type [$mimeType]. Only " . implode(', ', $mimeTypesAllowed) . " files are allowed.");
            }

            $pathFile = $this->createFilePathPending($userAuth, $fileId, $file);
            $fileUploaded = $this->fileHandler->upload($userAuth->userId, $pathFile, $file);
            $this->fileUploadedRepository->save($fileUploaded);

        } catch (\Exception $e) {
            if (!is_null($fileUploaded)) {
                $this->fileHandler->delete($fileUploaded->path);
            }
            throw new UploadFileException('Failed to upload file: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return ["url" => $fileUploaded->publicUrl];
    }

    /** Creates the path to the file that is pending upload.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param string $fileId The ID of the file to upload.
     * @param UploadedFile $file The file to upload.
     * @return string The path to the file that is pending upload.
     */
    private function createFilePathPending(UserAuth $userAuth, string $fileId, UploadedFile $file): string
    {
        return $this->config->getPathFilesPending() . "/{$userAuth->getEffectiveUserId()}/$fileId.{$file->extension()}";
    }
}