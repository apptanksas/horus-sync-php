<?php

namespace AppTank\Horus\Core\Config\Restriction;

use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\SyncAction;

/**
 * Class QueueActionSkipperValidatorEntityRestriction
 *
 * Implements a restriction that determines whether a synchronization action on a specific entity
 * should be skipped based on a custom callable function.
 *
 * This restriction provides flexibility to bypass certain actions (like insert, update, or delete)
 * for specific entities based on dynamic criteria evaluated at runtime.
 *
 * @package AppTank\Horus\Core\Config\Restriction
 *
 * @author John Ospina
 * Year: 2026
 */
readonly class QueueActionSkipperValidatorEntityRestriction implements EntityRestriction
{

    /**
     * QueueActionSkipperValidatorEntityRestriction constructor.
     *
     * @param string $entityName The name of the entity to apply the skip restriction to.
     * @param callable $skipperFunction A callable that determines if the action should be skipped.
     *                                  The function receives a SyncAction and an EntityData object,
     *                                  and must return a boolean:
     *                                  - true if the action should be skipped.
     *                                  - false if the action should proceed.
     *
     * @throws \InvalidArgumentException If the skipperFunction is not callable.
     */
    public function __construct(
        private string $entityName,
        private mixed  $skipperFunction
    )
    {
        if (!is_callable($this->skipperFunction)) {
            throw new \InvalidArgumentException("Filter function must be callable");
        }

    }

    /**
     * Retrieves the name of the entity this restriction applies to.
     *
     * @return string The entity name.
     */
    function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Determines whether a synchronization action on an entity should be skipped.
     *
     * @param SyncAction $action The synchronization action being performed.
     * @param EntityData $entityData The data of the entity associated with the action.
     *
     * @return bool True if the action should be skipped, false otherwise.
     */
    function mustBeSkipped(SyncAction $action, EntityData $entityData): bool
    {
        if (is_callable($this->skipperFunction)) {
            return $this->skipperFunction->__invoke($action, $entityData);
        }

        return false;
    }


}