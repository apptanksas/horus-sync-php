<?php

namespace AppTank\Horus\Core\Entity;

/**
 * Interface EntityDependsOn
 *
 * This interface defines a contract for classes that have dependencies on other entities.
 * It is used to retrieve the entity that the implementing class depends on.
 *
 * @package AppTank\Horus\Illuminate\Database
 */
interface EntityDependsOn
{
    /**
     * Get the entity that this class depends on.
     *
     * @return IEntitySynchronizable The entity that the class depends on.
     */
    public function dependsOn(): IEntitySynchronizable;

    /**
     * Get the reference to the parent entity that this class depends on.
     *
     * @return string|null The reference to the parent entity, typically a UUID or similar identifier.
     */
    public function getEntityParentId(): string|null;

    /**
     * Get the name of the parameter that holds the parent entity's identifier.
     *
     * @return string The name of the parameter that holds the parent entity's identifier.
     */
    public function getEntityParentParameterName(): string;
}
