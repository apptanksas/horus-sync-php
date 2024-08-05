<?php

namespace AppTank\Horus\Core\Repository;

interface MigrationSchemaRepository
{
    function getSchema(): array;
}