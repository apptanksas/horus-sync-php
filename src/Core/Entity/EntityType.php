<?php

namespace AppTank\Horus\Core\Entity;

/**
 * @internal Enum EntityType
 *
 * Defines the types of entities available in the system.
 *
 * @package AppTank\Horus\Core\Entity
 *
 * @author John Ospina
 * Year: 2024
 */
enum EntityType: string
{
    /**
     * Represents a writable entity type.
     */
    case WRITABLE = "writable";

    /**
     * Represents a readable entity type.
     */
    case READABLE = "readable";
}
