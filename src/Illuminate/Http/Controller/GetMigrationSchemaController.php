<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetMigrationSchemaController
 *
 * This controller handles HTTP requests for retrieving the migration schema. It uses the `MigrationSchemaRepository`
 * to fetch the current migration schema and provides it as a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetMigrationSchemaController extends Controller
{
    /**
     * Constructor for GetMigrationSchemaController.
     *
     * @param MigrationSchemaRepository $repository Repository for fetching the migration schema.
     */
    function __construct(
        private readonly MigrationSchemaRepository $repository
    )
    {
    }

    /**
     * Handle the incoming request to retrieve the migration schema.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response containing the migration schema or an error message.
     */
    function __invoke(Request $request): JsonResponse
    {
        try {
            return $this->responseSuccess($this->repository->getSchema());
        } catch (\Throwable $e) {
            report($e);
            return $this->responseServerError();
        }
    }
}
