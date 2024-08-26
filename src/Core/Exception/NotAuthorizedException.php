<?php

namespace AppTank\Horus\Core\Exception;

/**
 * @internal Class NotAuthorizedException
 *
 * Abstract base class for exceptions related to authorization issues. This class extends
 * `DomainException` to signify that the exception pertains to domain-specific authorization
 * errors in the application.
 *
 * @package AppTank\Horus\Core\Exception
 *
 * @author John Ospina
 * Year: 2024
 */
abstract class NotAuthorizedException extends \DomainException
{
    // This class serves as a base class for specific authorization-related exceptions.
}
