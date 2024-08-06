<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityUpdate;
use AppTank\Horus\Core\Model\QueueAction;

interface EntityRepository
{
    function insert(EntityInsert ...$operations): void;

    function update(EntityUpdate ...$operations): void;

    function delete(EntityUpdate ...$operations): void;

}