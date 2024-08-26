<?php

namespace AppTank\Horus\Core\Repository;

/**
 * @internal Interface MigrationSchemaRepository
 *
 * Defines the contract for retrieving migration schemas from a repository. Implementations of this
 * interface should handle the retrieval of schema information required for database migrations.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * Author: John Ospina
 * Year: 2024
 */
interface MigrationSchemaRepository
{
    /**
     * Retrieves the schema required for database migrations.
     *
     * @return array An array representing the schema, which may include table structures, columns, types, etc.
     */
    function getSchema(): array;
}
