<?php

namespace AppTank\Horus\Application\Search;

use AppTank\Horus\Application\Get\BaseGetEntities;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class SearchDataEntities extends BaseGetEntities
{
    function __construct(
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository
    )
    {

    }

    function __invoke(UserAuth $userAuth, string $entityName, array $ids = [], ?int $afterTimestamp = null): array
    {
        foreach ($ids as $id) {
            if ($this->accessValidatorRepository->canAccessEntity($userAuth,
                    new EntityReference($entityName, $id),
                    Permission::READ) === false) {
                throw new OperationNotPermittedException("No have access to entity $entityName with id $id");
            }
        }

        return $this->parseData(
            $this->entityRepository->searchEntities($userAuth->getEffectiveUserId(), $entityName, $ids, $afterTimestamp)
        );
    }
}