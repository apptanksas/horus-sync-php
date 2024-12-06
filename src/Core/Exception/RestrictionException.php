<?php

namespace AppTank\Horus\Core\Exception;

class RestrictionException extends ClientException
{
    public function __construct(string $message = "Restriction exception")
    {
        parent::__construct($message);
    }
}