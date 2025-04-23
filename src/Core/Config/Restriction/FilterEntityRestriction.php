<?php

namespace AppTank\Horus\Core\Config\Restriction;

use AppTank\Horus\Core\Config\Restriction\valueObject\ParameterFilter;

/**
 * Class FilterEntityRestriction
 *
 * This class implements the EntityRestriction interface and is used to filter entities based on specific parameters.
 * It contains the entity name and an array of ParameterFilter objects that define the filtering criteria.
 *
 * @package AppTank\Horus\Core\Config\Restriction
 */
readonly class FilterEntityRestriction implements EntityRestriction
{
    /**
     * FilterEntityRestriction constructor.
     *
     * @param string $entityName The name of the entity to be filtered.
     * @param ParameterFilter[] $parametersFilter The parameters to filter the entity.
     */
    public function __construct(
        public string $entityName,
        public array  $parametersFilter,
    )
    {

    }

    function getEntityName(): string
    {
        return $this->entityName;
    }
}