<?php

namespace AppTank\Horus\Core\Auth;

readonly class EntityGranted
{
    function __construct(
        string|int $userOwnerId,
        string     $entityName,
        string     $entityId,
    )
    {

    }
}