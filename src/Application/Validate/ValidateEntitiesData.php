<?php

namespace AppTank\Horus\Application\Validate;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\FeatureName;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\UserNotAuthorizedException;
use AppTank\Horus\Core\Hasher;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Model\EntityHash;
use AppTank\Horus\Core\Model\EntityHashValidation;
use AppTank\Horus\Core\Model\HashValidation;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

/**
 * @internal Class ValidateEntitiesData
 *
 * This class is responsible for validating the hashes of entities to ensure data integrity.
 * It compares the provided hashes with the current ones calculated from the database, returning the validation results.
 *
 * @author John Ospina
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
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository,
        private EntityMapper                    $entityMapper,
        private Config                          $config
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
     * @param string|int|null $userIdRequested Optional user ID to filter results. If null, uses the authenticated user's ID.
     * @return EntityHashValidation[] An array of validation results for each entity hash.
     */
    function __invoke(UserAuth $userAuth, array $entitiesHashes, string|int|null $userIdRequested = null): array
    {
        $output = [];
        $userIds = array_merge([$userAuth->userId], $userAuth->getUserOwnersId());
        $this->validateUserIdRequested($userIdRequested, $userIds);

        if (!is_null($userIdRequested)) {
            $userIds = $userIdRequested;
        }

        foreach ($entitiesHashes as $entityHash) {
            $currentHash = $this->calculateEntityHash($userAuth, $userIds, $entityHash);
            $output[] = new EntityHashValidation($entityHash->entityName, new HashValidation($entityHash->hash, $currentHash));
        }

        return $output;
    }

    private function calculateEntityHash(UserAuth $userAuth, array|string $userIds, EntityHash $entityHash): string
    {
        // Validate if the feature for validating data is disabled
        if (in_array(FeatureName::VALIDATE_DATA, $this->config->disabledFeatures)) {
            return $entityHash->hash;
        }

        $entityHashes = $this->entityRepository->getEntityHashes($userIds, $entityHash->entityName);

        $result = $this->filterValidateAccessEntity($userAuth, $entityHash->entityName, $entityHashes);
        $hashes = [];

        foreach ($result as $item) {
            $hashes[] = $item["sync_hash"];
        }

        return Hasher::hash($hashes);
    }

    private function filterValidateAccessEntity(UserAuth $userAuth, string $entityName, array $result): array
    {
        return array_values(array_filter($result, function ($item) use ($userAuth, $entityName) {
                $entityId = $item[EntitySynchronizable::ATTR_ID];
                // Validate is primary entity and has read permission
                if ($this->entityMapper->isPrimaryEntity($entityName) && $userAuth->hasGranted($entityName, $entityId, Permission::READ)) {
                    return true;
                }
                return $this->accessValidatorRepository->canAccessEntity($userAuth, new EntityReference($entityName, $entityId), Permission::READ);
            })
        );
    }

    private function validateUserIdRequested(string|int|null $userIdRequested, array $userIds): void
    {
        if (!is_null($userIdRequested) && !in_array($userIdRequested, $userIds)) {
            throw new UserNotAuthorizedException("User not authorized to access the requested user data");
        }
    }
}