<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class EntityHashValidation
 *
 * Represents the validation of a hash for an entity. This class is used to store the entity name
 * along with its hash validation result.
 *
 * @package AppTank\Horus\Core\Model
 *
 * Author: John Ospina
 * Year: 2024
 */
readonly class EntityHashValidation
{
    /**
     * Constructor for the EntityHashValidation class.
     *
     * @param string $entityName The name of the entity.
     * @param HashValidation $hashValidation The result of the hash validation.
     */
    function __construct(
        public string         $entityName,
        public HashValidation $hashValidation
    )
    {

    }
}
