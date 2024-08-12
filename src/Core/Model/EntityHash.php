<?php

namespace AppTank\Horus\Core\Model;

readonly class EntityHash
{
    function __construct(
        public string $entityName,
        public string $hash,
    )
    {

    }
}