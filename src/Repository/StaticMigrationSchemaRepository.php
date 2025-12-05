<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Repository\CacheRepository;
use AppTank\Horus\Core\Repository\MigrationSchemaRepository;
use AppTank\Horus\Horus;

/**
 * @internal StaticMigrationSchemaRepository
 * StaticMigrationSchemaRepository implements the MigrationSchemaRepository interface
 * and provides a method to retrieve the migration schema for all entities.
 */
readonly class StaticMigrationSchemaRepository implements MigrationSchemaRepository
{

    function __construct(
        private CacheRepository $cacheRepository
    )
    {

    }

    /**
     * Retrieves the schema for all entities managed by the entity mapper.
     *
     * This method gets the entity mapper from the Horus, retrieves the list of entities,
     * and fetches their schema definitions. Throws an exception if an entity class is not found.
     *
     * @return array An array of schemas for all entities.
     * @throws \DomainException If an entity class is not found.
     */
    function getSchema(): array
    {
        $ttlOneHour = 3600;

        return $this->cacheRepository->remember("migration_scheme", $ttlOneHour, function () {

            $entityMapper = Horus::getInstance()->getEntityMapper();
            $entities = $entityMapper->getMap();
            $schema = [];

            foreach ($entities as $entityMap) {
                $entityClass = $entityMapper->getEntityClass($entityMap->name);

                if (!class_exists($entityClass)) {
                    throw new \DomainException("Entity class not found");
                }

                $schema[] = $entityClass::schema();
            }

            return $schema;
        });
    }
}
