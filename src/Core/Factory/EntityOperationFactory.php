<?php

namespace AppTank\Horus\Core\Factory;

use AppTank\Horus\Core\Model\EntityDelete;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;

class EntityOperationFactory
{
    public static function createEntityInsert(string|int         $ownerId,
                                              string             $entity,
                                              array              $data,
                                              \DateTimeImmutable $actionedAt): EntityInsert
    {
        return new EntityInsert($ownerId, $entity, $actionedAt, $data);
    }

    public static function createEntityUpdate(
        string|int         $ownerId,
        string             $entity,
        string             $id,
        array              $attributes,
        \DateTimeImmutable $actionedAt): EntityUpdate
    {
        return new EntityUpdate($ownerId, $entity, $id, $actionedAt, $attributes);
    }

    public static function createEntityDelete(
        string|int         $ownerId,
        string             $entity,
        string             $id,
        \DateTimeImmutable $actionedAt): EntityDelete
    {
        return new EntityDelete($ownerId, $entity, $id, $actionedAt);
    }


}