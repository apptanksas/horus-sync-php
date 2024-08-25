<?php

namespace AppTank\Horus\Core\Auth;

use AppTank\Horus\Core\Entity\EntityReference;

readonly class EntityGranted
{
    function __construct(
        public string|int      $userOwnerId,
        public EntityReference $entityReference,
        public AccessLevel     $accessLevel
    )
    {

    }
}