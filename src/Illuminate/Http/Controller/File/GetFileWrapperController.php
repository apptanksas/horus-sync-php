<?php

namespace AppTank\Horus\Illuminate\Http\Controller\File;

use AppTank\Horus\Application\File\SearchFileUrl;
use AppTank\Horus\Core\Exception\FileNotFoundException;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class GetFileWrapperController extends Controller
{
    private readonly SearchFileUrl $useCase;

    /**
     * Constructor for GetFileWrapperController.
     *
     * @param FileUploadedRepository $repository Repository for fetching the file URL.
     */
    function __construct(FileUploadedRepository $repository)
    {
        $this->useCase = new SearchFileUrl($repository);
    }

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