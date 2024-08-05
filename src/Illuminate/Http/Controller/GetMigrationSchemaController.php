<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetMigrationSchemaController extends Controller
{

    function __construct(
        private readonly MigrationSchemaRepository $repository
    )
    {

    }

    function __invoke(Request $request): JsonResponse
    {
        try {
            return $this->responseSuccess($this->repository->getSchema());
        } catch (\Throwable $e) {
            throw $e;
            return $this->responseServerError();
        }
    }
}