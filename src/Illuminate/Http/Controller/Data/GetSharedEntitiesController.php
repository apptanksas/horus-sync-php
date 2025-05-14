<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Get\GetDataSharedEntities;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Repository\CacheRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetSharedEntitiesController
 *
 * This controller is responsible for handling requests related to shared entities.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller\Data
 */
class GetSharedEntitiesController extends Controller
{
    private readonly GetDataSharedEntities $useCase;

    function __construct(
        EntityRepository $entityRepository,
        CacheRepository  $cacheRepository,
        Config           $config
    )
    {
        $this->useCase = new GetDataSharedEntities(
            $entityRepository,
            $cacheRepository,
            $config
        );
    }

    /**
     * Handles the incoming request to get shared entities.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response containing the shared entities.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            return $this->responseSuccess(
                $this->useCase->__invoke(
                    $this->getUserAuthenticated()
                )
            );
        });
    }

}