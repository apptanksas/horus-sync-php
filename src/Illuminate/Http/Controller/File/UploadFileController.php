<?php

namespace AppTank\Horus\Illuminate\Http\Controller\File;

use AppTank\Horus\Application\File\UploadFile;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class UploadFileController
 *
 * Represents a controller for uploading files. This class defines a method for uploading a file and saving it to the repository.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller\File
 *
 * @author John Ospina
 * Year: 2024
 */
class UploadFileController extends Controller
{
    private readonly UploadFile $useCase;

    /**
     * Constructor for the UploadFileController class.
     *
     * @param IFileHandler $fileHandler The file handler.
     * @param FileUploadedRepository $fileUploadedRepository The repository for uploaded files.
     */
    function __construct(
        IFileHandler           $fileHandler,
        FileUploadedRepository $fileUploadedRepository
    )
    {
        $this->useCase = new UploadFile(
            $fileHandler,
            $fileUploadedRepository,
            Horus::getInstance()->getConfig());
    }

    /**
     * Handles the file upload request.
     *
     * @param Request $request The HTTP request.
     * @return JsonResponse The JSON response.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {

            $fileId = $request->input('id');

            if (!$fileId) {
                return $this->responseBadRequest('No file ID was provided');
            }

            if ($this->isNotUUID($fileId)) {
                return $this->responseBadRequest('Invalid file ID, must be a valid UUID');
            }

            if (!$request->hasFile('file')) {
                return $this->responseBadRequest('No file was uploaded');
            }

            return $this->responseSuccess(
                $this->useCase->__invoke(
                    $this->getUserAuthenticated(),
                    $request->input('id'),
                    $request->file('file')
                )
            );
        });
    }


}