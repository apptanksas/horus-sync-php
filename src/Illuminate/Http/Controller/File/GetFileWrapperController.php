<?php

namespace AppTank\Horus\Illuminate\Http\Controller\File;

use AppTank\Horus\Application\File\SearchFileInfo;
use AppTank\Horus\Core\Exception\FileNotFoundException;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal Class GetFileWrapperController
 *
 * This controller handles HTTP requests for retrieving a file that has been uploaded. It uses the
 * `SearchFileInfo` use case to search for the file URL by its reference ID and provides it as a response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetFileWrapperController extends Controller
{
    private readonly SearchFileInfo $useCase;

    /**
     * Constructor for GetFileWrapperController.
     *
     * @param FileUploadedRepository $repository Repository for fetching the file URL.
     */
    function __construct(FileUploadedRepository $repository)
    {
        $this->useCase = new SearchFileInfo($repository);
    }

    /**
     * Handle the incoming request to retrieve a file that has been uploaded.
     *
     * @param string $fileId The reference ID of the file to search.
     * @param Request $request The HTTP request object.
     * @return Response Response containing the file content or an error message.
     */
    function __invoke(string $fileId, Request $request): Response
    {

        if ($this->isNotUUID($fileId)) {
            return $this->responseBadRequest('Invalid file ID, must be a valid UUID');
        }

        try {
            $result = $this->useCase->__invoke($fileId);
            $imageUrl = $result["url"];

            $response = Http::get($imageUrl);

            if ($response->failed()) {
                return $this->responseNotFound('File not found');
            }

            return response($response->body(), 200)
                ->header('Content-Type', $response->header('Content-Type'));

        } catch (FileNotFoundException $e) {
            return $this->responseNotFound($e->getMessage());
        }
    }

}