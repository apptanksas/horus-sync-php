<?php

namespace AppTank\Horus\Core\Config\Restriction\valueObject;

readonly class ParameterFilter
{
    public function __construct(
        public string $parameterName,
        public mixed  $parameterValue,
    )
    {

    }
}
