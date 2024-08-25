<?php

namespace AppTank\Horus\Illuminate\Http\Controller;

use AppTank\Horus\Application\Sync\SyncQueueActions;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Illuminate\Http\Controller;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostSyncQueueActionsController extends Controller
{
    private SyncQueueActions $useCase;

    function __construct(
        ITransactionHandler             $transactionHandler,
        QueueActionRepository           $queueActionRepository,
        EntityRepository                $entityRepository,
        EntityAccessValidatorRepository $accessValidatorRepository,
        IEventBus                       $eventBus
    )
    {
        $this->useCase = new SyncQueueActions($transactionHandler,
            $queueActionRepository,
            $entityRepository,
            $accessValidatorRepository,
            $eventBus);
    }

    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            $this->useCase->__invoke(
                $this->getUserAuthenticated(),
                ...$this->parseRequestToQueueActions($request)
            );
            return $this->responseAccepted();
        });
    }


    /**
     * @param Request $request
     * @return QueueAction[]
     */
    private function parseRequestToQueueActions(Request $request): array
    {
        $queueActions = [];
        $requestData = $request->all();
        $dateUtil = new DateTimeUtil();
        $userId = $this->getUserAuthenticated()->userId;
        $ownerId = $this->getUserAuthenticated()->getEffectiveUserId();

        foreach ($requestData as $itemAction) {
            $queueActions[] = new QueueAction(
                SyncAction::newInstance($itemAction['action']),
                $itemAction['entity'],
                $this->createEntityOperation($ownerId, $itemAction),
                \DateTimeImmutable::createFromMutable($dateUtil->parseDateTime($itemAction['actioned_at'])),
                now()->toDateTimeImmutable(),
                $userId,
                $ownerId
            );
        }


        return $queueActions;
    }

    private function createEntityOperation(string|int $ownerId, array $itemData): EntityOperation
    {
        $dateUtil = new DateTimeUtil();
        $actionedAt = \DateTimeImmutable::createFromMutable($dateUtil->parseDateTime($itemData['actioned_at']));

        return match (strtolower($itemData['action'])) {
            'insert' => EntityOperationFactory::createEntityInsert(
                $ownerId,
                $itemData['entity'],
                $itemData['data'],
                $actionedAt
            ),
            'update' => EntityOperationFactory::createEntityUpdate(
                $ownerId,
                $itemData['entity'],
                $itemData['data']["id"],
                $itemData['data']["attributes"],
                $actionedAt
            ),
            'delete' => EntityOperationFactory::createEntityDelete(
                $ownerId,
                $itemData['entity'],
                $itemData['data']["id"],
                $actionedAt
            ),
        };
    }
}