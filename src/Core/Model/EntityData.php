<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class EntityData
 *
 * Represents data associated with an entity. It includes methods to set related entities and retrieve entity data.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
class EntityData
{
    /**
     * Constructor for the EntityData class.
     *
     * @param string $name The name of the entity.
     * @param array $data An associative array containing entity data.
     */
    public function __construct(
        public readonly string $name,
        private array          $data = []
    )
    {
    }

    /**
     * Sets related entities for a "one-of-many" relationship.
     *
     * @param string $relationName The name of the relationship.
     * @param array $relatedEntities An array of related entities.
     *
     * @return void
     */
    function setEntitiesRelatedOneOfMany(string $relationName, array $relatedEntities): void
    {
        unset($this->data[$relationName]);
        $this->data["_$relationName"] = $relatedEntities;
    }

    /**
     * Sets a related entity for a "one-to-one" relationship.
     *
     * @param string $relationName The name of the relationship.
     * @param EntityData $relatedEntity The related entity data.
     *
     * @return void
     */
    function setEntitiesRelatedOneToOne(string $relationName, EntityData $relatedEntity): void
    {
        unset($this->data[$relationName]);
        $this->data["_$relationName"] = $relatedEntity;
    }

    /**
     * Retrieves the entity data.
     *
     * @return array The associative array containing entity data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Retrieves the ID of the entity.
     *
     * @return string The entity ID.
     */
    function getEntityId(): string
    {
        return $this->data['id'];
    }
}
