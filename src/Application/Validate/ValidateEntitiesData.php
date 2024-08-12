<?php

namespace AppTank\Horus\Application\Validate;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Model\EntityHash;
use AppTank\Horus\Core\Model\EntityHashValidation;
use AppTank\Horus\Core\Model\HashValidation;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class ValidateEntitiesData
{
    function __construct(
        private EntityRepository $entityRepository
    )
    {
    }


    /**
     * @param EntityHash[] $entitiesHashes
     * @return EntityHashValidation[]
     */
    function __invoke(string|int $ownerUserId, array $entitiesHashes): array
    {
        $output = [];

        foreach ($entitiesHashes as $entityHash) {

            $result = $this->entityRepository->getEntityHashes($ownerUserId, $entityHash->entityName);
            $hashes = [];

            foreach ($result as $item) {
                $hashes[] = $item["sync_hash"];
            }

            $currentHash = Hasher::hash($hashes);

            $output[] = new EntityHashValidation($entityHash->entityName, new HashValidation($entityHash->hash, $currentHash));
        }

        return $output;
    }
}