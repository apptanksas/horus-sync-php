<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Get\GetQueueActions;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetQueueActionsController extends Controller
{
    private readonly GetQueueActions $useCase;

    function __construct(QueueActionRepository $repository)
    {
        $this->useCase = new GetQueueActions($repository);
    }

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