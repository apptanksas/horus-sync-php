<?php

namespace AppTank\Horus\Core\Mapper;

use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;

/**
 * @internal Class EntityMapper
 *
 * Manages the mapping between entity names and their respective classes, as well as handling entity maps and paths.
 * It provides methods to register entities, retrieve their classes, and manage mapping configurations.
 *
 * @package AppTank\Horus\Core\Mapper
 *
 * @author John Ospina
 * Year: 2024
 */
class EntityMapper
{
    private array $entities = [];
    private array $map = [];
    private array $paths = [];

    /**
     * Retrieves the class name associated with the given entity name.
     *
     * @param string $entityName The name of the entity.
     *
     * @return string The fully qualified class name of the entity.
     *
     * @throws ClientException If the entity name is not found in the mapping.
     */
    function getEntityClass(string $entityName): string
    {
        return $this->entities[$entityName] ?? throw new ClientException("Entity $entityName not found");
    }

    /**
     * Registers an entity class with the mapper.
     *
     * @param string|WritableEntitySynchronizable $entityClass The class name of the entity or an instance of `EntitySynchronizable`.
     *
     * @return void
     */
    function pushEntity(string $entityClass): void
    {
        $this->entities[$entityClass::getEntityName()] = $entityClass;
    }

    /**
     * Adds an entity map to the mapper.
     *
     * @param EntityMap $map The entity map to add.
     *
     * @return void
     */
    function pushMap(EntityMap $map): void
    {
        $this->map[] = $map;

        $paths = $map->generateArrayPaths();

        if (count($paths) == 1) {
            $this->paths[] = $paths;
            return;
        }

        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * Retrieves the registered entities.
     *
     * @return array An associative array of entity names to their class names.
     */
    function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Retrieves the registered entity maps.
     *
     * @return EntityMap[] An array of `EntityMap` objects.
     */
    function getMap(): array
    {
        return $this->map;
    }

    /**
     * Retrieves the registered paths.
     *
     * @return array An array of paths generated from entity maps.
     */
    function getPaths(): array
    {
        return $this->paths;
    }
}
