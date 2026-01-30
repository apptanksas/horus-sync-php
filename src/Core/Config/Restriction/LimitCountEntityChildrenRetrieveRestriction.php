<?php

namespace AppTank\Horus\Core\Config\Restriction;

/**
 * @internal Class LimitCountEntityChildrenRestriction
 *
 * Implements a restriction that limits the number of child entities that can be retrieved
 *
 * @package AppTank\Horus\Core\Config\Restriction
 *
 * @author John Ospina
 * Year: 2026
 */
class LimitCountEntityChildrenRetrieveRestriction implements EntityRestriction
{
    public function __construct(
        public string $entityName,
        public int    $limit
    )
    {

    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }
}