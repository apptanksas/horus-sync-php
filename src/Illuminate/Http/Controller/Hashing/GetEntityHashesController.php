<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Hashing;

use AppTank\Horus\Application\Get\GetEntityHashes;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetEntityHashesController
 *
 * This controller handles HTTP requests for retrieving entity hashes. It uses the `GetEntityHashes` use case
 * to fetch the hashes for a specific entity.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetEntityHashesController extends Controller
{
    private readonly GetEntityHashes $useCase;

    /**
     * Constructor for GetEntityHashesController.
     *
     * @param EntityRepository $repository Repository for entity operations.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access.
     * @param EntityMapper $entityMapper Mapper for converting entities.
     */
    function __construct(EntityRepository                $repository,
                         EntityAccessValidatorRepository $accessValidatorRepository,
                         EntityMapper                    $entityMapper)
    {
        $this->useCase = new GetEntityHashes($repository, $accessValidatorRepository, $entityMapper);
    }

    /**
     * Handle the incoming request to fetch entity hashes.
     *
     * @param string $entityName The name of the entity whose hashes are to be retrieved.
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response with the result of the `GetEntityHashes` use case.
     */
    function __invoke(string $entityName, Request $request): JsonResponse
    {
        return $this->handle(function () use ($entityName) {
            return $this->responseSuccess(
                $this->useCase->__invoke(
                    $this->getUserAuthenticated(),
                    $entityName
                )
            );
        });
    }
}
