<?php

namespace AppTank\Horus\Core\Config;

/**
 * Class Config
 *
 * Represents the configuration settings for the application.
 *
 * @package AppTank\Horus\Core\Config
 *
 * Author: John Ospina
 * Year: 2024
 */
class Config
{
    /**
     * @param bool $validateAccess Indicates whether access validation is enabled.
     * @param string|null $connectionName The name of the database connection, or null if not specified.
     * @param bool $usesUUIDs Indicates whether UUIDs are used as primary keys.
     */
    function __construct(
        public bool    $validateAccess = false,
        public ?string $connectionName = null,
        public bool    $usesUUIDs = false
    )
    {

    }
}
