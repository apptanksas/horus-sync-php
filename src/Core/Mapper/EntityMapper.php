<?php

namespace AppTank\Horus\Core\Mapper;

use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

class EntityMapper
{
    private array $entities = [];
    private array $map = [];

    /**
     * Get the Eloquent class entity
     *
     * @param string $entityName
     * @return string
     */
    function getEntityClass(string $entityName): string
    {
        return $this->entities[$entityName] ?? throw new \InvalidArgumentException("Entity $entityName not found");
    }

    /**
     * @param string|EntitySynchronizable $entityClass
     * @return void
     */
    function pushEntity(string $entityClass): void
    {
        $this->entities[$entityClass::getEntityName()] = $entityClass;
    }

    function pushMap(EntityMap $map): void
    {
        $this->map[] = $map;
    }

    function getEntities(): array
    {
        return $this->entities;
    }

    function getMap(): array
    {
        return $this->map;
    }
}