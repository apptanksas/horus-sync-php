<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class GetDataEntities extends BaseGetEntities
{
    function __construct(
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository
    )
    {

    }

    function __invoke(UserAuth $userAuth, ?int $afterTimestamp = null): array
    {

        $result = array_merge(
            $this->searchOwnEntities($userAuth->getEffectiveUserId(), $afterTimestamp),
            $this->searchEntitiesGranted($userAuth, $afterTimestamp)
        );

        return $this->parseData($result);
    }

    /**
     * Search entities by user id must be own entities
     *
     * @param string|int $userId
     * @param int|null $afterTimestamp
     * @return EntityData[]
     */
    private function searchOwnEntities(string|int $userId, ?int $afterTimestamp = null): array
    {
        if (is_null($afterTimestamp)) {
            return $this->entityRepository->searchAllEntitiesByUserId($userId);
        }
        return $this->entityRepository->searchEntitiesAfterUpdatedAt($userId, $afterTimestamp);
    }

    private function searchEntitiesGranted(UserAuth $userAuth, ?int $afterTimestamp = null): array
    {
        $output = [];

        foreach ($userAuth->entityGrants as $entityGranted) {

            // Check if user has permission to read entity
            if ($this->accessValidatorRepository->canAccessEntity($userAuth,
                $entityGranted->entityReference, Permission::READ)) {

                $result = $this->entityRepository->searchEntities($entityGranted->userOwnerId,
                    $entityGranted->entityReference->entityName,
                    [$entityGranted->entityReference->entityId],
                    $afterTimestamp
                );
                $output = array_merge($output, $result);
            }
        }

        return $output;
    }

}
