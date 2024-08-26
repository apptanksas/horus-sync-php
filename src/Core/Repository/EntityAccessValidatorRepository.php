<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;

interface EntityAccessValidatorRepository
{
    public function canAccessEntity(UserAuth $userAuth, EntityReference $entityReference, Permission $permission): bool;

}