<?php

namespace AppTank\Horus\Illuminate\Database\Concerns;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;

/**
 * Trait HasSynchronizableDependencies
 *
 * Provides optimized dependency resolution for EntitySynchronizable models implementing EntityDependsOn.
 * Checks whether the relation is already loaded in memory or if an in-memory cache method exists
 * on the parent model before executing a database query.
 *
 * @package AppTank\Horus\Illuminate\Database\Concerns
 */
trait HasSynchronizableDependencies
{
    /**
     * Resolves a parent synchronizable dependency efficiently by checking if the relation
     * is already loaded, or using a static instance cache if available on the parent class,
     * or falling back to Eloquent relationship query.
     *
     * @param string $relationName The name of the Eloquent relation method.
     * @param string $parentClass The class of the parent synchronizable entity.
     * @param string $foreignKey The foreign key column holding the parent entity's ID.
     * @param string $ownerKey The primary key column of the parent entity (defaults to 'id').
     * @return IEntitySynchronizable|null
     */
    protected function resolveCachedDependency(
        string $relationName,
        string $parentClass,
        string $foreignKey,
        string $ownerKey = 'id'
    ): ?IEntitySynchronizable {
        if ($this->relationLoaded($relationName) && $this->getRelation($relationName) !== null) {
            return $this->getRelation($relationName);
        }

        $parentId = $this->getAttribute($foreignKey);
        if ($parentId === null) {
            return null;
        }

        if (method_exists($parentClass, 'findCached')) {
            $parent = $parentClass::findCached($parentId);
            if ($parent !== null) {
                return $parent;
            }
        }

        return $this->belongsTo($parentClass, $foreignKey, $ownerKey)->withTrashed()->first();
    }
}
