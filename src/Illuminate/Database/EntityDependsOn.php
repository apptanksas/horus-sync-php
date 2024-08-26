<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;

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
}
