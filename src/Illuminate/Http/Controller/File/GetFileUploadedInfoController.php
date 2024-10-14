<?php

namespace AppTank\Horus\Illuminate\Http\Controller\File;

use AppTank\Horus\Application\File\SearchFileUrl;
use AppTank\Horus\Core\Exception\FileNotFoundException;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetFileUploadedInfoController
 *
 * This controller handles HTTP requests for retrieving information about a file that has been uploaded. It uses the
 * `SearchFileUrl` use case to search for the file URL by its reference ID and provides it as a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetFileUploadedInfoController extends Controller
{

    private readonly SearchFileUrl $useCase;

    /**
     * Constructor for GetFileUploadedInfoController.
     *
     * @param FileUploadedRepository $repository Repository for fetching the file URL.
     */
    function __construct(FileUploadedRepository $repository)
    {
        $this->useCase = new SearchFileUrl($repository);
    }

    /**
     * Handle the incoming request to retrieve information about a file that has been uploaded.
     *
     * @param string $fileId The reference ID of the file to search.
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response containing the file URL and MIME type or an error message.
     */
    function __invoke(string $fileId, Request $request): JsonResponse
    {
        try {

            if ($this->isNotUUID($fileId)) {
                return $this->responseBadRequest('Invalid file ID, must be a valid UUID');
            }

            return $this->responseSuccess($this->useCase->__invoke($fileId));
        } catch (FileNotFoundException $e) {
            return $this->responseNotFound($e->getMessage());
        }
    }

}