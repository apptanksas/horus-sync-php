<?php

namespace AppTank\Horus\Core\Exception;

class ClientException extends \Exception
{
    public function __construct(string $message, public readonly string|null $codeError = null, public readonly array $context = [])
    {
        parent::__construct($message);
    }
}