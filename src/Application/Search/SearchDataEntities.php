<?php

namespace AppTank\Horus\Application\Search;

use AppTank\Horus\Application\Get\BaseGetEntities;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class SearchDataEntities extends BaseGetEntities
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(UserAuth $userAuth, string $entityName, array $ids = [], ?int $afterTimestamp = null): array
    {
        $result = $this->entityRepository->searchEntities($userAuth->userId, $entityName, $ids, $afterTimestamp);
        return $this->parseData($result);
    }
}