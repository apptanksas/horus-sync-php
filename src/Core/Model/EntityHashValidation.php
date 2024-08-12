<?php

namespace AppTank\Horus\Core\Model;

readonly class EntityHashValidation
{
    function __construct(
        public string         $entityName,
        public HashValidation $hashValidation
    )
    {

    }
}