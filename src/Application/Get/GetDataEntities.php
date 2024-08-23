<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
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
        if (is_null($afterTimestamp)) {
            $result = $this->entityRepository->searchAllEntitiesByUserId($userAuth->userId);
        } else {
            $result = $this->entityRepository->searchEntitiesAfterUpdatedAt($userAuth->userId, $afterTimestamp);
        }

        return $this->parseData($result);
    }

}
