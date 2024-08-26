<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Get\GetDataEntities;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetDataEntitiesController
 *
 * This controller handles HTTP requests for retrieving data entities. It uses the `GetDataEntities` use case
 * to fetch data based on user authentication and query parameters.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetDataEntitiesController extends Controller
{
    private readonly GetDataEntities $useCase;

    /**
     * Constructor for GetDataEntitiesController.
     *
     * @param EntityRepository                $entityRepository                Repository for entity operations.
     * @param EntityAccessValidatorRepository $entityAccessValidatorRepository Repository for entity access validation.
     */
    function __construct(EntityRepository $entityRepository,
                         EntityAccessValidatorRepository $entityAccessValidatorRepository)
    {
        $this->useCase = new GetDataEntities($entityRepository, $entityAccessValidatorRepository);
    }

    /**
     * Handle the incoming request to fetch data entities.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response with the result of the `GetDataEntities` use case.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            return $this->responseSuccess(
                $this->useCase->__invoke(
                    $this->getUserAuthenticated(),
                    $request->query("after")
                )
            );
        });
    }
}
