<?php

namespace AppTank\Horus\Application\Validate;

use AppTank\Horus\Core\Auth\UserAuth;
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
     * @param UserAuth $userAuth
     * @param EntityHash[] $entitiesHashes
     * @return EntityHashValidation[]
     */
    function __invoke(UserAuth $userAuth, array $entitiesHashes): array
    {
        $output = [];

        foreach ($entitiesHashes as $entityHash) {

            $result = $this->entityRepository->getEntityHashes($userAuth->userId, $entityHash->entityName);
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