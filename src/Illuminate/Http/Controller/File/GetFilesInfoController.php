<?php

namespace AppTank\Horus\Illuminate\Http\Controller\File;

use AppTank\Horus\Application\File\SearchFiles;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetFilesInfoController extends Controller
{
    private readonly SearchFiles $useCase;

    function __construct(FileUploadedRepository $repository)
    {
        $this->useCase = new SearchFiles($repository);
    }

    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {

            $ids = $request->get("ids", []);

            foreach ($ids as $id) {
                if ($this->isNotUUID($id)) {
                    return $this->responseBadRequest('Invalid file ID, must be a valid UUID');
                }
            }

            return $this->responseSuccess($this->useCase->__invoke($this->getUserAuthenticated(), $ids));
        });
    }
}