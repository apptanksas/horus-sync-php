<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Get\GetDataEntities;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetDataEntitiesController extends Controller
{

    private readonly GetDataEntities $useCase;

    function __construct(EntityRepository $entityRepository)
    {
        $this->useCase = new GetDataEntities($entityRepository);
    }

    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            return $this->responseSuccess($this->useCase->__invoke($this->getUserAuthenticated(), $request->query("after")));
        });
    }
}