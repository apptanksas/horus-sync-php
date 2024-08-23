<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class GetDataEntities extends BaseGetEntities
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(UserAuth $userAuth, ?int $afterTimestamp = null): array
    {
        $result = array_merge(
            $this->searchOwnEntities($userAuth->userId, $afterTimestamp),
            $this->searchEntitiesGranted($userAuth)
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

    private function searchEntitiesGranted(UserAuth $userAuth): array
    {
        $output = [];

        foreach ($userAuth->entityGrants as $entityGranted) {
            $result = $this->entityRepository->searchEntities($entityGranted->userOwnerId, $entityGranted->entityName, [$entityGranted->entityId]);
            $output = array_merge($output, $result);
        }

        return $output;
    }

}
