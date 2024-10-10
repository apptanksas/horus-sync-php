<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\File\SearchFileUrl;
use AppTank\Horus\Core\Exception\FileNotFoundException;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetFileUploadedInfoController extends Controller
{
    private readonly SearchFileUrl $useCase;

    function __construct(FileUploadedRepository $repository)
    {
        $this->useCase = new SearchFileUrl($repository);
    }

    function __invoke(string $fileId, Request $request): JsonResponse
    {
        try {
            return $this->responseSuccess($this->useCase->__invoke($fileId));
        } catch (FileNotFoundException $e) {
            return $this->responseNotFound($e->getMessage());
        }
    }

}