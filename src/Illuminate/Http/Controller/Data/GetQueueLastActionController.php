<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Get\GetQueueLastAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class GetQueueLastActionController
 *
 * This controller handles HTTP requests for retrieving the last queue action for a user. It utilizes the `GetQueueLastAction`
 * use case to fetch the most recent queue action based on the authenticated user and returns the result as a JSON response.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class GetQueueLastActionController extends Controller
{
    private readonly GetQueueLastAction $useCase;

    /**
     * Constructor for GetQueueLastActionController.
     *
     * @param QueueActionRepository $repository Repository for fetching queue actions.
     */
    function __construct(QueueActionRepository $repository)
    {
        parent::__construct();
        $this->useCase = new GetQueueLastAction($repository);
    }

    /**
     * Handle the incoming request to retrieve the last queue action.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response containing the last queue action or an error message.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            return $this->responseSuccess($this->useCase->__invoke($this->getUserAuthenticated()));
        });
    }
}
