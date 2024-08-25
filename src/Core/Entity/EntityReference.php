<?php

namespace AppTank\Horus\Core\Entity;

readonly class EntityReference
{
    function __construct(
        public string $entityName,
        public string $entityId
    )
    {

    }
}