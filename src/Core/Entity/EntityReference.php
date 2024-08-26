<?php

namespace AppTank\Horus\Core\Entity;

/**
 * Class EntityReference
 *
 * Represents a reference to an entity, consisting of the entity's name and ID.
 *
 * @package AppTank\Horus\Core\Entity
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class EntityReference
{
    /**
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     */
    function __construct(
        public string $entityName,
        public string $entityId
    )
    {

    }
}
