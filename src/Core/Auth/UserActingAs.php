<?php

namespace AppTank\Horus\Core\Auth;

readonly class UserActingAs
{
    function __construct(
        public string|int $userId
    )
    {

    }
}