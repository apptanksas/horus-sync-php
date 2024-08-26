<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;

/**
 * @internal Class GetDataEntities
 *
 * Retrieves data entities for a user, including their own entities and entities granted to them by others.
 * This class extends the BaseGetEntities class and utilizes the EntityRepository and EntityAccessValidatorRepository
 * for accessing and validating the entities.
 *
 * Author: John Ospina
 * Year: 2024
 */
readonly class GetDataEntities extends BaseGetEntities
{
    /**
     * GetDataEntities constructor.
     *
     * @param EntityRepository $entityRepository Repository for accessing entity data.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access.
     */
    function __construct(
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository
    )
    {

    }

    /**
     * Invokes the GetDataEntities class to retrieve entities.
     *
     * Combines the results of the user's own entities and the entities granted to them.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param int|null $afterTimestamp Optional timestamp to filter entities updated after this time.
     * @return array An array of parsed entity data.
     */
    function __invoke(UserAuth $userAuth, ?int $afterTimestamp = null): array
    {

        $result = array_merge(
            $this->searchOwnEntities($userAuth->getEffectiveUserId(), $afterTimestamp),
            $this->searchEntitiesGranted($userAuth, $afterTimestamp)
        );

        return $this->parseData($result);
    }

    /**
     * Searches for the user's own entities.
     *
     * @param string|int $userId The ID of the user whose entities are being searched.
     * @param int|null $afterTimestamp Optional timestamp to filter entities updated after this time.
     * @return EntityData[] An array of the user's own entities.
     */
    private function searchOwnEntities(string|int $userId, ?int $afterTimestamp = null): array
    {
        if (is_null($afterTimestamp)) {
            return $this->entityRepository->searchAllEntitiesByUserId($userId);
        }
        return $this->entityRepository->searchEntitiesAfterUpdatedAt($userId, $afterTimestamp);
    }

    /**
     * Searches for entities granted to the user.
     *
     * Checks if the user has permission to access each granted entity.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param int|null $afterTimestamp Optional timestamp to filter entities updated after this time.
     * @return array An array of entities granted to the user.
     */
    private function searchEntitiesGranted(UserAuth $userAuth, ?int $afterTimestamp = null): array
    {
        $output = [];

        foreach ($userAuth->entityGrants as $entityGranted) {

            // Check if user has permission to read entity
            if ($this->accessValidatorRepository->canAccessEntity($userAuth,
                $entityGranted->entityReference, Permission::READ)) {

                $result = $this->entityRepository->searchEntities($entityGranted->userOwnerId,
                    $entityGranted->entityReference->entityName,
                    [$entityGranted->entityReference->entityId],
                    $afterTimestamp
                );
                $output = array_merge($output, $result);
            }
        }

        return $output;
    }

}
