<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;

interface EntityDependsOn
{
    public function dependsOn(): IEntitySynchronizable;
}