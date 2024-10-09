<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Upload\UploadFile;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadFileController extends Controller
{
    private readonly UploadFile $useCase;

    function __construct(
        IFileHandler           $fileHandler,
        FileUploadedRepository $fileUploadedRepository
    )
    {
        $this->useCase = new UploadFile($fileHandler, $fileUploadedRepository);
    }

    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {

            $fileId = $request->input('id');

            if (!$fileId) {
                return $this->responseBadRequest('No file ID was provided');
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