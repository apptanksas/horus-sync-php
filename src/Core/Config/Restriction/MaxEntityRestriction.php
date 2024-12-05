<?php

namespace AppTank\Horus\Core\Config\Restriction;

/**
 * Interface EntityRestriction
 *
 * Represents a restriction that can be applied to an entity.
 *
 * @package AppTank\Horus\Core\Config\Restriction
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class MaxEntityRestriction implements EntityRestriction
{
    public function __construct(
        public string $entityName,
        public int    $value
    )
    {

    }
}