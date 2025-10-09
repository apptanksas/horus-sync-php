<?php

namespace AppTank\Horus\Illuminate\Http\Controller\Data;

use AppTank\Horus\Application\Sync\SyncQueueActions;
use AppTank\Horus\Core\Bus\IEventBus;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Model\QueueAction;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Core\Transaction\ITransactionHandler;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Http\Controller;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @internal Class PostSyncQueueActionsController
 *
 * This controller handles HTTP requests to synchronize queue actions. It uses the `SyncQueueActions` use case to process
 * the incoming queue actions and execute the synchronization logic. The controller parses the request data to create `QueueAction`
 * objects and processes them within a transaction.
 *
 * @package AppTank\Horus\Illuminate\Http\Controller
 */
class PostSyncQueueActionsController extends Controller
{
    private SyncQueueActions $useCase;

    /**
     * Constructor for PostSyncQueueActionsController.
     *
     * @param ITransactionHandler $transactionHandler Transaction handler for managing transactions.
     * @param QueueActionRepository $queueActionRepository Repository for handling queue actions.
     * @param EntityRepository $entityRepository Repository for entity-related operations.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access.
     * @param IEventBus $eventBus Event bus for dispatching events.
     */
    function __construct(
        ITransactionHandler             $transactionHandler,
        QueueActionRepository           $queueActionRepository,
        EntityRepository                $entityRepository,
        EntityAccessValidatorRepository $accessValidatorRepository,
        FileUploadedRepository          $fileUploadedRepository,
        IEventBus                       $eventBus,
        EntityMapper                    $entityMapper
    )
    {
        parent::__construct();

        $this->useCase = new SyncQueueActions(
            $transactionHandler,
            $queueActionRepository,
            $entityRepository,
            $accessValidatorRepository,
            $fileUploadedRepository,
            $eventBus,
            Horus::getInstance()->getFileHandler(),
            $entityMapper,
            Horus::getInstance()->getConfig()
        );
    }

    /**
     * Handle the incoming request to synchronize queue actions.
     *
     * @param Request $request The HTTP request object.
     * @return JsonResponse JSON response indicating the success or failure of the synchronization.
     */
    function __invoke(Request $request): JsonResponse
    {
        return $this->handle(function () use ($request) {
            return $this->idempotency("post_sync_queue_actions", $request, function () use ($request) {
                $this->useCase->__invoke(
                    $this->getUserAuthenticated(),
                    ...$this->parseRequestToQueueActions($request)
                );
                return $this->responseAccepted();
            }, onCache: function () {
                return $this->responseAccepted();
            });
        });
    }

    /**
     * Parse the request data to create an array of QueueAction objects.
     *
     * @param Request $request The HTTP request object.
     * @return QueueAction[] Array of QueueAction objects created from the request data.
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
                $itemAction['data']["id"],
                $this->createEntityOperation($ownerId, $itemAction),
                \DateTimeImmutable::createFromMutable($dateUtil->parseDateTime($itemAction['actioned_at'])),
                now()->toDateTimeImmutable(),
                $userId,
                $ownerId
            );
        }

        return $queueActions;
    }

    /**
     * Create an EntityOperation object based on the provided action data.
     *
     * @param string|int $ownerId The ID of the owner of the entity.
     * @param array $itemData The action data from the request.
     * @return EntityOperation The created EntityOperation object.
     */
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
            'update', 'move' => EntityOperationFactory::createEntityUpdate(
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
