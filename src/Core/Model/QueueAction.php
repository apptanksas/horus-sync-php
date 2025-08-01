<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\SyncAction;

/**
 * @internal Class QueueAction
 *
 * Represents an action in a queue for synchronization. This class captures details about an action,
 * including the type of action, the entity involved, the operation performed, and timing information.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class QueueAction
{
    /**
     * Constructor for the QueueAction class.
     *
     * @param SyncAction $action The type of synchronization action.
     * @param string $entity The name of the entity involved.
     * @param EntityOperation $operation The operation performed.
     * @param \DateTimeImmutable $actionedAt The timestamp when the action was performed.
     * @param \DateTimeImmutable $syncedAt The timestamp when the action was synchronized.
     * @param int|string $userId The ID of the user who initiated the action.
     * @param int|string $ownerId The owner ID of the entity.
     * @param bool $bySystem Indicates if the action was performed by the system.
     */
    function __construct(
        public SyncAction         $action,
        public string             $entity,
        public string             $entityId,
        public EntityOperation    $operation,
        public \DateTimeImmutable $actionedAt,
        public \DateTimeImmutable $syncedAt,
        public int|string         $userId,
        public int|string         $ownerId,
        public bool               $bySystem = false,
    )
    {

    }


    function cloneWithUsers(string|int $userId, string|int $ownerId): self
    {
        return new self(
            $this->action,
            $this->entity,
            $this->entityId,
            $this->operation->cloneWithOwnerId($ownerId),
            $this->actionedAt,
            $this->syncedAt,
            $userId,
            $ownerId,
            $this->bySystem
        );
    }


}
