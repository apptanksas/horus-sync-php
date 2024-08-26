<?php

namespace AppTank\Horus\Application\Search;

use AppTank\Horus\Application\Get\BaseGetEntities;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class SearchDataEntities extends BaseGetEntities
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(string|int $userOwnerId, string $entityName, array $ids = [], ?int $afterTimestamp = null): array
    {
        $result = $this->entityRepository->searchEntities($userOwnerId, $entityName, $ids, $afterTimestamp);
        return $this->parseData($result);
    }
}