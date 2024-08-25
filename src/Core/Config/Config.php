<?php

namespace AppTank\Horus\Core\Config;

class Config
{
    function __construct(
        public bool    $validateAccess = false,
        public ?string $connectionName = null,
        public bool    $usesUUIDs = false
    )
    {

    }
}