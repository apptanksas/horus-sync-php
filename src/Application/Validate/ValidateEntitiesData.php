<?php

namespace AppTank\Horus\Application\Validate;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Model\EntityHash;
use AppTank\Horus\Core\Model\EntityHashValidation;
use AppTank\Horus\Core\Model\HashValidation;
use AppTank\Horus\Core\Repository\EntityRepository;

/**
 * @internal Class ValidateEntitiesData
 *
 * This class is responsible for validating the hashes of entities to ensure data integrity.
 * It compares the provided hashes with the current ones calculated from the database, returning the validation results.
 *
 * Author: John Ospina
 * Year: 2024
 */
readonly class ValidateEntitiesData
{
    /**
     * ValidateEntitiesData constructor.
     *
     * @param EntityRepository $entityRepository Repository for managing entities and retrieving their data.
     */
    function __construct(
        private EntityRepository $entityRepository
    )
    {
    }

    /**
     * Validates the provided entity hashes against the current data in the repository.
     *
     * This method iterates over the provided entity hashes, retrieves the corresponding hashes from the repository,
     * and compares them using a hashing algorithm. The result is an array of `EntityHashValidation` objects
     * indicating whether the provided hash matches the current one.
     *
     * @param UserAuth $userAuth The authenticated user requesting the validation.
     * @param EntityHash[] $entitiesHashes The array of entity hashes to be validated.
     * @return EntityHashValidation[] An array of validation results for each entity hash.
     */
    function __invoke(UserAuth $userAuth, array $entitiesHashes): array
    {
        $output = [];

        foreach ($entitiesHashes as $entityHash) {

            $result = $this->entityRepository->getEntityHashes($userAuth->getEffectiveUserId(), $entityHash->entityName);
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