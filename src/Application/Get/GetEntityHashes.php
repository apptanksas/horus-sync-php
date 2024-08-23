<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\EntityRepository;

class GetEntityHashes
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(UserAuth $userAuth, string $entityName): array
    {
        return $this->entityRepository->getEntityHashes($userAuth->userId, $entityName);
    }
}