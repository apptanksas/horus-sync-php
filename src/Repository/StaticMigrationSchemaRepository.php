<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\HorusContainer;

class StaticMigrationSchemaRepository implements MigrationSchemaRepository
{

    function getSchema(): array
    {
        $entityMapper = HorusContainer::getInstance()->getEntityMapper();
        $entities = $entityMapper->getMap();
        $schema = [];

        foreach ($entities as $entityMap) {

            $entityClass = $entityMapper->getEntityClass($entityMap->name);

            if (!class_exists($entityClass))
                throw new \DomainException("Entity class not found");
            $schema[] = $entityClass::schema();
        }

        return $schema;
    }
}