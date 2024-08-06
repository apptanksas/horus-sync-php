<?php

namespace AppTank\Horus\Core\Model;

class EntityDelete extends EntityOperation
{
    function __construct(
        string|int         $ownerId,
        string             $entity,
        string             $id,
        \DateTimeImmutable $actionedAt,
    )
    {
        parent::__construct($ownerId, $entity, $id, $actionedAt);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id
        ];
    }
}