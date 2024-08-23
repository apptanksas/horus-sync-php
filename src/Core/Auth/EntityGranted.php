<?php

namespace AppTank\Horus\Core\Auth;

readonly class EntityGranted
{
    function __construct(
        public string|int $userOwnerId,
        public string     $entityName,
        public string     $entityId,
    )
    {

    }
}