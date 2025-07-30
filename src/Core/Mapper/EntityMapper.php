<?php

namespace AppTank\Horus\Core\Mapper;

use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;
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
    private array $parametersReferenceFile = [];

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

        if (count($paths) == 1 && is_string($paths[0])) {
            $this->paths = array_merge($this->paths, [$paths]);
            return;
        }

        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * Returns the parameters that are of type reference file for a given entity.
     *
     * @param string $entityName The name of the entity.
     * @return string[] The parameters that are of type reference file.
     */
    function getParametersReferenceFile(string $entityName): array
    {

        if (isset($this->parametersReferenceFile[$entityName])) {
            return $this->parametersReferenceFile[$entityName];
        }
        /**
         * @var $entityClass EntitySynchronizable
         */
        $entityClass = $this->getEntityClass($entityName);
        $parameters = $entityClass::parameters();
        $parametersReferenceFile = array_map(fn($parameter) => $parameter->name, array_filter($parameters, fn($parameter) => $parameter->type === SyncParameterType::REFERENCE_FILE));

        $this->parametersReferenceFile[$entityName] = $parametersReferenceFile;

        return $parametersReferenceFile;
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

    /**
     * Checks if the given entity is a primary entity (i.e., it has no parent entity).
     *
     * @param string $entity The name of the entity to check.
     * @return bool True if the entity is a primary entity, otherwise false.
     */
    function isPrimaryEntity(string $entity): bool
    {
        return $this->getHierarchicalLevel($entity) === 0;
    }

    /**
     * Gets the hierarchical level of the given entity.
     *
     * This method searches through all registered paths to find where the entity appears
     * and returns the lowest hierarchical level (closest to root) where it is found.
     * Level 0 represents the root level, level 1 represents first child level, etc.
     *
     * @param string $entity The name of the entity to get the hierarchical level for.
     * @return int The hierarchical level of the entity (0 for root level).
     */
    private function getHierarchicalLevel(string $entity): int
    {
        $minLevel = PHP_INT_MAX;

        foreach ($this->paths as $path) {
            // Handle both single paths and nested path arrays
            $pathArray = is_array($path[0]) ? $path[0] : $path;

            $position = array_search($entity, $pathArray);
            if ($position !== false) {
                $minLevel = min($minLevel, $position);
            }
        }

        // If entity is not found in any path, return 0 (assume root level)
        return $minLevel === PHP_INT_MAX ? 0 : $minLevel;
    }

}
