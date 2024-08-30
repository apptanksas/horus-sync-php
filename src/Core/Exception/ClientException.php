<?php

namespace AppTank\Horus\Core\Exception;

class ClientException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}