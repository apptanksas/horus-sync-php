<?php

namespace AppTank\Horus\Core\Exception;

class OperationNotPermittedException extends NotAuthorizedException
{
    public function __construct(string $message = "Operation not permitted")
    {
        parent::__construct($message);
    }
}