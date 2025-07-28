<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Illuminate\Util\EntitiesDataParser;

/**
 * @internal
 *
 * Abstract class BaseGetEntities
 *
 * Provides functionality to parse and structure data from entities, including handling related entities.
 * This class is intended to be extended by specific implementations that require parsing of entity data.
 *
 * @author John Ospina
 * @year 2024
 */
readonly abstract class BaseGetEntities
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
    protected function parseData(array $entities): array
    {
        return EntitiesDataParser::parseToArrayRaw($entities);
    }
}