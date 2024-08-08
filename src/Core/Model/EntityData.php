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

    function setEntitiesRelated(string $relationName, array $relatedEntities): void
    {
        $this->data["_$relationName"] = $relatedEntities;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}