<?php

namespace AppTank\Horus\Core\Model;

abstract class EntityOperation
{
    public function __construct(
        public readonly string|int         $ownerId,
        public readonly string             $entity,
        public readonly string             $id,
        public readonly \DateTimeImmutable $actionedAt,
    )
    {

    }

    public abstract function toArray(): array;
}