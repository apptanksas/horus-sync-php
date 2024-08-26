<?php

namespace AppTank\Horus\Core\Model;

class EntityData
{


    public function __construct(
        public readonly string $name,
        private array          $data = []
    )
    {
    }

    function setEntitiesRelatedOneOfMany(string $relationName, array $relatedEntities): void
    {
        $this->data["_$relationName"] = $relatedEntities;
    }

    function setEntitiesRelatedOneToOne(string $relationName, EntityData $relatedEntity): void
    {
        $this->data["_$relationName"] = $relatedEntity;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    function getEntityId(): string
    {
        return $this->data['id'];
    }
}