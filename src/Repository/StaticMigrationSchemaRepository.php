<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\HorusContainer;

class StaticMigrationSchemaRepository implements MigrationSchemaRepository
{

    function getSchema(): array
    {
        $entities = HorusContainer::getInstance()->getEntities();
        $schema = [];

        foreach ($entities as $entity) {
            if (!class_exists($entity))
                throw new \DomainException("Entity class not found");
            $schema[] = $entity::schema();
        }

        return $schema;
    }
}