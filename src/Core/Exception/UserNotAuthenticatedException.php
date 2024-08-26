<?php

namespace AppTank\Horus\Core\Exception;

/**
 * @internal Class UserNotAuthenticatedException
 *
 * Exception thrown when a user is not authenticated. This exception extends
 * from `NotAuthorizedException`, which is used for general authorization errors.
 *
 * @package AppTank\Horus\Core\Exception
 *
 * @author John Ospina
 * Year: 2024
 */
class UserNotAuthenticatedException extends NotAuthorizedException
{
    // No additional functionality or properties are defined in this subclass.
}
