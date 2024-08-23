<?php

namespace AppTank\Horus\Core\Auth;

readonly class UserAuth
{
    /**
     * @param string|int $userId
     * @param EntityGranted[] $entityGrants
     */
    function __construct(
        public string|int $userId,
        public array      $entityGrants = []
    )
    {

    }
}