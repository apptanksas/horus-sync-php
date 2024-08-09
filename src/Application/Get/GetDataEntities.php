<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Repository\EntityRepository;

readonly class GetDataEntities extends BaseGetEntities
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(string|int $userOwnerId, ?int $afterTimestamp = null): array
    {
        if (is_null($afterTimestamp)) {
            $result = $this->entityRepository->searchAllEntitiesByUserId($userOwnerId);
        } else {
            $result = $this->entityRepository->searchEntitiesAfterUpdatedAt($userOwnerId, $afterTimestamp);
        }

        return $this->parseData($result);
    }

}
