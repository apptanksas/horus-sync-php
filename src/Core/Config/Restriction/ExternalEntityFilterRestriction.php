<?php

namespace AppTank\Horus\Core\Config\Restriction;


use AppTank\Horus\Core\Config\Restriction\valueObject\ParameterValueTransformer;
use AppTank\Horus\Core\Model\EntityData;

/**
 * Class ExternalEntityFilterRestriction
 *
 * Implements a restriction that filters entities based on an external callable filter function
 * and optionally transforms parameter values using ParameterValueTransformer objects.
 *
 * This restriction allows for complex filtering logic that is applied to entities after they
 * are retrieved from the database. It supports transforming specific entity parameters based
 * on custom transformation functions.
 *
 * @package AppTank\Horus\Core\Config\Restriction
 *
 * @author John Ospina
 * Year: 2026
 */
class ExternalEntityFilterRestriction implements EntityRestriction
{

    private array $parameterValueTransformers = [];

    /**
     * ExternalEntityFilterRestriction constructor.
     *
     * @param string $entityName The name of the entity to apply the filter restriction to.
     * @param callable $filterFunction A callable that determines whether an entity should be filtered out.
     *                                 The function receives an EntityData object and returns a boolean:
     *                                 - true if the entity should be filtered (removed from results)
     *                                 - false if the entity should be kept in results
     * @param ParameterValueTransformer[] $parameterValueTransformers Optional array of transformers to modify
     *                                                                 entity parameter values before filtering.
     *
     * @throws \InvalidArgumentException If the filterFunction is not callable.
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

    /**
     * Retrieves the name of the entity this restriction applies to.
     *
     * @return string The entity name.
     */
    function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Determines whether an entity should be filtered out based on the filter function.
     *
     * @param EntityData $entityData The entity data to evaluate.
     *
     * @return bool True if the entity should be filtered out, false otherwise.
     */
    function mustBeFilter(EntityData $entityData): bool
    {
        if (is_callable($this->filterFunction)) {
            return $this->filterFunction->__invoke($entityData);
        }

        return false;
    }

    /**
     * Transforms the values of entity parameters using the configured ParameterValueTransformer objects.
     *
     * This method iterates through all entity data and applies transformations to parameters
     * that have a corresponding transformer registered. Parameters without a transformer
     * are returned unchanged.
     *
     * @param EntityData $entityData The entity data whose parameters should be transformed.
     *
     * @return EntityData A new EntityData object with transformed parameter values.
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