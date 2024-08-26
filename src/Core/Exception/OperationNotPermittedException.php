<?php

namespace AppTank\Horus\Core\Exception;

/**
 * @internal Class OperationNotPermittedException
 *
 * Exception thrown when an operation is not permitted. This exception extends
 * from `NotAuthorizedException` to indicate that the operation fails due to
 * authorization issues.
 *
 * @package AppTank\Horus\Core\Exception
 *
 * @author John Ospina
 * Year: 2024
 */
class OperationNotPermittedException extends NotAuthorizedException
{
    /**
     * Constructor for OperationNotPermittedException.
     *
     * @param string $message The exception message. Defaults to "Operation not permitted".
     */
    public function __construct(string $message = "Operation not permitted")
    {
        parent::__construct($message);
    }
}
