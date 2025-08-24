<?php

namespace AppTank\Horus\Core\Exception;

class RestrictionException extends ClientException
{
    public function __construct(string $message = "Restriction exception", string|null $code = null, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}