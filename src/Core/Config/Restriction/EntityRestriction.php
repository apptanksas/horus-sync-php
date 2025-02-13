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
interface EntityRestriction
{
    /**
     * Retrieves the name of the entity associated with the restriction.
     *
     * @return string The name of the entity.
     */
    function getEntityName(): string;
}