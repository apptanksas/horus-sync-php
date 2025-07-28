<?php

namespace AppTank\Horus\Illuminate\Util;

use AppTank\Horus\Core\Model\EntityData;

class EntitiesDataParser
{
    /**
     * Parses a list of entities into a structured array format.
     *
     * This method processes each entity, extracting its data and handling related entities recursively.
     * Related entities that are arrays or instances of `EntityData` are processed accordingly.
     *
     * @param EntityData[] $entities Array of entities to be parsed.
     * @return array Returns a structured array with entity names and their associated data.
     */

    static function parseToArrayRaw(array $entities): array
    {
        $output = [];

        foreach ($entities as $entity) {

            $data = $entity->getData();

            foreach ($data as $key => $value) {

                // Check if the key represents a related entity
                if (str_starts_with($key, "_") and is_array($value)) {
                    $data[$key] = self::parseToArrayRaw($value);
                }

                if (str_starts_with($key, "_") and $value instanceof EntityData) {
                    $data[$key] = ["entity" => $value->name, "data" => $value->getData()];
                }

            }
            $output[] = ["entity" => $entity->name, "data" => $data];
        }

        return $output;
    }
}