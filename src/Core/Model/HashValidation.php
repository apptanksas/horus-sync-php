<?php

namespace AppTank\Horus\Core\Model;

readonly class HashValidation
{
    public bool $matched;

    function __construct(
        public string $expected,
        public string $obtained
    )
    {
        $this->matched = $this->expected === $this->obtained;
    }
}