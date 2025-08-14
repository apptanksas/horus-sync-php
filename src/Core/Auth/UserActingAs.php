<?php

namespace AppTank\Horus\Core\Auth;

/**
 * Class UserActingAs
 *
 * Represents a user who is acting on behalf of another user.
 * This includes the ID of the user who is being acted as.
 *
 * @author John Ospina
 * @deprecated This class is deprecated and will be removed in a future version. Its unnecessary to validate owner entities.
 * Year: 2024
 */
readonly class UserActingAs
{
    /**
     * @param string|int $userId ID of the user being acted as.
     */
    function __construct(
        public string|int $userId
    )
    {

    }
}