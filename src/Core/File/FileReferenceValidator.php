<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Model\FileUploaded;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use Illuminate\Support\Facades\Log;

class FileReferenceValidator implements IFileReferenceValidator
{

    private FilePathGenerator $filePathGenerator;

    function __construct(
        private readonly EntityRepository       $entityRepository,
        private readonly FileUploadedRepository $fileUploadedRepository,
        private readonly IFileHandler           $fileHandler,
        private readonly Config                 $config
    )
    {
        $this->filePathGenerator = new FilePathGenerator($this->entityRepository, $this->config);
    }

    /**
     * Validates a file reference, ensuring the file exists and is properly linked to the specified entity.
     *
     * @param UserAuth $userAuth
     * @param string $referenceFile
     * @param EntityReference $entityReference
     * @return void
     * @throws \Exception
     */
    function validate(UserAuth $userAuth, string $referenceFile, EntityReference $entityReference): void
    {
        $fileUploaded = $this->fileUploadedRepository->search($referenceFile);

        if (is_null($fileUploaded)) {
            throw new \Exception("File not found");
        }

        if ($fileUploaded->status == SyncFileStatus::LINKED) {
            // The file is already linked, no need to validate further
            return;
        }

        $pathFileFinal = $fileUploaded->path;
        $urlFinal = $fileUploaded->publicUrl;
        $status = $fileUploaded->status;

        $pathFileDestination = $this->filePathGenerator->create($userAuth, $entityReference) . basename($fileUploaded->path);
        $copiedSuccess = false;

        $pathPending = $fileUploaded->path;

        if ($this->fileHandler->copy($pathPending, $pathFileDestination)) {
            $pathFileFinal = $pathFileDestination;
            $urlFinal = $this->fileHandler->generateUrl($pathFileDestination);
            $status = SyncFileStatus::LINKED;
            $copiedSuccess = true;
        } else {
            Log::error("[Horus:File]Error copying file", [
                'referenceFile' => $referenceFile,
                'file' => $fileUploaded->path,
                'destination' => $pathFileDestination,
                'userId' => $userAuth->userId,
                'entity' => $entityReference->entityName,
                'entityId' => $entityReference->entityId
            ]);
        }

        $fileUploaded = new FileUploaded(
            $fileUploaded->id,
            $fileUploaded->mimeType,
            $pathFileFinal,
            $urlFinal,
            $fileUploaded->ownerId,
            $status
        );

        $this->fileUploadedRepository->save($fileUploaded);

        // If the file was successfully copied to the new location, delete the old file
        if ($copiedSuccess) {
            $this->fileHandler->delete($pathPending);
        }
    }
}