<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Search\SearchDataEntities;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchEntitiesController extends Controller
{

    private readonly SearchDataEntities $useCase;

    function __construct(EntityRepository $entityRepository)
    {
        $this->useCase = new SearchDataEntities($entityRepository);
    }

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
                    $after)
            );
        });
    }
}