<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Get\GetQueueLastAction;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Illuminate\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetQueueLastActionController extends Controller
{
    private readonly GetQueueLastAction $useCase;

    function __construct(QueueActionRepository $repository)
    {
        $this->useCase = new GetQueueLastAction($repository);
    }

    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            return $this->responseSuccess($this->useCase->__invoke($this->getUserAuthenticated()));
        });
    }
}