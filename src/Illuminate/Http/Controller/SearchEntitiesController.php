<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Search\SearchDataEntities;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class SearchEntitiesController
 *
 * This controller handles HTTP requests to search for data entities. It uses the `SearchDataEntities` use case to perform
 * the search operation based on the provided parameters. The controller processes incoming search requests and returns
 * the search results in a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class SearchEntitiesController extends Controller
{
    private readonly SearchDataEntities $useCase;

    /**
     * Constructor for SearchEntitiesController.
     *
     * @param EntityRepository                $entityRepository Repository for entity-related operations.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access.
     */
    function __construct(
        EntityRepository                $entityRepository,
        EntityAccessValidatorRepository $accessValidatorRepository
    )
    {
        $this->useCase = new SearchDataEntities($entityRepository, $accessValidatorRepository);
    }

    /**
     * Handle the incoming request to search for data entities.
     *
     * @param string $entity The name of the entity to search.
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response with the search results.
     */
    function __invoke(string $entity, Request $request): JsonResponse
    {
        return $this->handle(function () use ($request, $entity) {
            $ids = [];
            $after = $request->query("after");

            if ($request->has("ids")) {
                $ids = array_map(fn($id) => trim($id), explode(",", $request->get("ids")));
            }

            return $this->responseSuccess(
                $this->useCase->__invoke(
                    $this->getUserAuthenticated(),
                    $entity,
                    $ids,
                    $after
                )
            );
        });
    }
}
