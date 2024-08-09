<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class GetDataEntities
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

    /**
     * @param EntityData[] $entities
     * @return array
     */
    private function parseData(array $entities): array
    {
        $output = [];

        foreach ($entities as $entity) {
            $data = $entity->getData();

            foreach ($data as $key => $value) {
                // Validate if is a related entity
                if (str_starts_with($key, "_")) {
                    $data[$key] = $this->parseData($value);
                }
            }
            $output[] = ["entity" => $entity->name, "data" => $data];
        }

        return $output;
    }
}