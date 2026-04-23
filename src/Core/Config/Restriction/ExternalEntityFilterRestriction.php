<?php

namespace AppTank\Horus\Core\Config\Restriction;


use AppTank\Horus\Core\Config\Restriction\valueObject\ParameterValueTransformer;
use AppTank\Horus\Core\Model\EntityData;

class ExternalEntityFilterRestriction implements EntityRestriction
{

    private array $parameterValueTransformers = [];

    /**
     * @param string $entityName
     * @param callable $filterFunction
     * @param ParameterValueTransformer[] $parameterValueTransformers
     */
    public function __construct(
        private readonly string $entityName,
        private readonly mixed  $filterFunction,
        array                   $parameterValueTransformers = []
    )
    {
        if (!is_callable($this->filterFunction)) {
            throw new \InvalidArgumentException("Filter function must be callable");
        }

        foreach ($parameterValueTransformers as $parameterValueTransformer) {
            $this->parameterValueTransformers[$parameterValueTransformer->parameterName] = $parameterValueTransformer;
        }
    }

    function getEntityName(): string
    {
        return $this->entityName;
    }

    function mustBeFilter(EntityData $entityData): bool
    {
        if (is_callable($this->filterFunction)) {
            return $this->filterFunction->__invoke($entityData);
        }

        return false;
    }

    /**
     * @param EntityData $entityData
     * @return EntityData
     */
    function transformValues(EntityData $entityData): EntityData
    {
        $data = [];

        $entityDataValues = $entityData->getData();
        foreach ($entityDataValues as $parameter => $value) {
            if (isset($this->parameterValueTransformers[$parameter])) {
                $data[$parameter] = $this->parameterValueTransformers[$parameter]?->transformerValue($entityData, $parameter, $value) ?? $value;
            } else {
                $data[$parameter] = $value;
            }
        }

        return new EntityData(
            name: $entityData->name,
            data: $data
        );
    }


}