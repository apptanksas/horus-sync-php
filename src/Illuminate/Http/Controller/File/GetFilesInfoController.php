<?php

namespace AppTank\Horus\Illuminate\Http\Controller\File;

use AppTank\Horus\Application\File\SearchFilesInfo;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetFilesInfoController
 *
 * This controller handles HTTP requests for retrieving information about files that have been uploaded. It uses the
 * `SearchFiles` use case to search for the files by their reference IDs and provides the information as a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetFilesInfoController extends Controller
{
    private readonly SearchFilesInfo $useCase;

    /**
     * Constructor for GetFilesInfoController.
     *
     * @param FileUploadedRepository $repository Repository for fetching the file information.
     */
    function __construct(FileUploadedRepository $repository)
    {
        $this->useCase = new SearchFilesInfo($repository);
    }

    /**
     * Handle the incoming request to retrieve information about files that have been uploaded.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response containing the file information or an error message.
     */
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