<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Repository\EntityRepository;

class GetEntityHashes
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(string|int $userOwnerId, string $entityName): array
    {
        return $this->entityRepository->getEntityHashes($userOwnerId, $entityName);
    }
}