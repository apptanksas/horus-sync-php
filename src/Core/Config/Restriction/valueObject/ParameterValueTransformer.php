<?php

namespace AppTank\Horus\Core\Config\Restriction\valueObject;

use AppTank\Horus\Core\Model\EntityData;

readonly class ParameterValueTransformer
{
    public function __construct(
        public string $parameterName,
        private mixed  $transformerValueFunction,
    )
    {
        if (!is_callable($this->transformerValueFunction)) {
            throw new \InvalidArgumentException("Filter function must be callable");
        }
    }

    public function transformerValue(EntityData $entityData, string $parameter, mixed $value): mixed
    {
        return $this->transformerValueFunction->__invoke($entityData, $parameter, $value);
    }
}
