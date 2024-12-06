<?php

namespace AppTank\Horus\Core\Config\Restriction;

/**
 * Interface EntityRestriction
 *
 * Represents a restriction that can be applied to an entity indicating a limit on the number of entities that can be created.
 *
 * @package AppTank\Horus\Core\Config\Restriction
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class MaxCountEntityRestriction implements EntityRestriction
{
    public function __construct(
        public string $entityName,
        public int $maxCount
    )
    {

    }

    function getEntityName(): string
    {
        return $this->entityName;
    }
}