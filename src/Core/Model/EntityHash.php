<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class EntityHash
 *
 * Represents a hash value associated with an entity. This class is used to store the hash and
 * the name of the entity to which it corresponds.
 *
 * @package AppTank\Horus\Core\Model
 *
 * Author: John Ospina
 * Year: 2024
 */
readonly class EntityHash
{
    /**
     * Constructor for the EntityHash class.
     *
     * @param string $entityName The name of the entity.
     * @param string $hash The hash value associated with the entity.
     */
    function __construct(
        public string $entityName,
        public string $hash,
    )
    {

    }
}
