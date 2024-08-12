<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Get\GetEntityHashes;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetEntityHashesController extends Controller
{
    private readonly GetEntityHashes $useCase;

    function __construct(EntityRepository $repository)
    {
        $this->useCase = new GetEntityHashes($repository);
    }

    function __invoke(string $entityName, Request $request): JsonResponse
    {
        return $this->handle(function () use ($entityName) {
            return $this->responseSuccess($this->useCase->__invoke(
                $this->getAuthenticatedUserId(),
                $entityName
            ));
        });
    }
}