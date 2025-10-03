<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Get\GetQueueActions;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetQueueActionsController
 *
 * This controller handles HTTP requests for retrieving queue actions. It uses the `GetQueueActions` use case
 * to fetch queue actions based on the provided request parameters and returns the results as a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetQueueActionsController extends Controller
{

    private readonly GetQueueActions $useCase;

    /**
     * Constructor for GetQueueActionsController.
     *
     * @param QueueActionRepository $queueActionRepository Repository for fetching queue actions.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access.
     * @param EntityMapper $entityMapper Mapper for converting entities.
     */
    function __construct(
        QueueActionRepository           $queueActionRepository,
        EntityAccessValidatorRepository $accessValidatorRepository,
        EntityMapper                    $entityMapper,
    )
    {
        parent::__construct();
        $this->useCase = new GetQueueActions($queueActionRepository, $accessValidatorRepository, $entityMapper);
    }

    /**
     * Handle the incoming request to retrieve queue actions.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response containing the queue actions or an error message.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            $afterTimestamp = $request->get("after");
            $excludeDateTimes = [];

            if ($request->has("exclude")) {
                $excludeDateTimes = array_map(fn($datetime) => intval(trim($datetime)), explode(",", $request->get("exclude")));
            }

            return $this->responseSuccess($this->useCase->__invoke(
                $this->getUserAuthenticated(),
                $afterTimestamp,
                $excludeDateTimes
            ));
        });
    }
}
