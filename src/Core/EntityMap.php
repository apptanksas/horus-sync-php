<?php

namespace AppTank\Horus\Core;

/**
 * @internal Class EntityMap
 *
 * Represents a mapping of an entity with its related entities.
 * This class is used to define the structure and relationships of entities.
 *
 * @package AppTank\Horus\Core
 */
readonly class EntityMap
{
    /**
     * Constructor for the EntityMap class.
     *
     * @param string $name The name of the entity.
     * @param EntityMap[] $related An array of related EntityMap instances.
     */
    public function __construct(
        public string $name,
        public array  $related = []
    )
    {

    }

    /**
     * Generates a list of paths for the entity map.
     *
     * This method creates an array of paths representing the hierarchical structure of the entity and its related entities.
     * Each path is represented as an array of entity names.
     *
     * @param string $separator The separator used to concatenate entity names in the paths (not used in the current implementation).
     * @return array|string[] An array of paths, where each path is an array of entity names.
     */
    public function generateArrayPaths(): array
    {
        $output = [];

        // If there are no related entities, return a single path with the current entity's name
        if (empty($this->related)) {
            return [$this->name];
        }

        // Generate paths for related entities
        $groupPaths = $this->flattenToArrayOfArrays(array_map(fn($map) => $map->generateArrayPaths(), $this->related));

        // Combine the current entity's name with paths from related entities
        foreach ($groupPaths as $paths) {
            $output[] = array_merge([$this->name], $paths);
        }

        return $this->flattenToArrayOfArrays($output);
    }

    private function flattenToArrayOfArrays(array $input): array
    {
        $result = [];

        $flatten = function ($item) use (&$flatten, &$result) {
            if (!is_array($item)) {
                return; // ignorar elementos que no son arrays
            }

            // Si todos los elementos internos NO son arrays → ya es una unidad válida
            $allValuesAreScalars = array_reduce($item, function ($carry, $value) {
                return $carry && !is_array($value);
            }, true);

            if ($allValuesAreScalars) {
                $result[] = $item;
            } else {
                foreach ($item as $subItem) {
                    $flatten($subItem);
                }
            }
        };

        foreach ($input as $element) {
            $flatten($element);
        }

        return $result;
    }
}
