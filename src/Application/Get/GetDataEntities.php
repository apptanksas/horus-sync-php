<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Repository\EntityRepository;

readonly class GetDataEntities
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {

    }

    function __invoke(string|int $userOwnerId): array
    {
        $result = $this->entityRepository->searchAllEntitiesByUserId($userOwnerId);
        $output = [];

        foreach ($result as $entity) {
            $output[] = ["entity" => $entity->name, "data" => $entity->getData()];
        }

        return $output;
    }
}