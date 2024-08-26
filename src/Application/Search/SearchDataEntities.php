<?php

namespace AppTank\Horus\Application\Search;

use AppTank\Horus\Application\Get\BaseGetEntities;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;

/**
 * @internal Class SearchDataEntities
 *
 * Handles the search for specific entities, ensuring that the user has the necessary permissions to access each entity.
 * This class extends `BaseGetEntities` and utilizes the `EntityRepository` and `EntityAccessValidatorRepository`
 * to fetch and validate entity access.
 *
 * Author: John Ospina
 * Year: 2024
 */
readonly class SearchDataEntities extends BaseGetEntities
{
    /**
     * SearchDataEntities constructor.
     *
     * @param EntityRepository $entityRepository Repository for accessing entities.
     * @param EntityAccessValidatorRepository $accessValidatorRepository Repository for validating entity access permissions.
     */
    function __construct(
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository
    )
    {

    }

    /**
     * Invokes the SearchDataEntities class to search for entities by their IDs.
     *
     * Validates if the authenticated user has the necessary permissions to access each entity. If the user
     * lacks access to any of the entities, an OperationNotPermittedException is thrown. If access is granted,
     * the entities are retrieved and returned as an array.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param string $entityName The name of the entity to search.
     * @param array $ids The list of entity IDs to search for.
     * @param int|null $afterTimestamp Optional. The timestamp to filter entities updated after a certain time.
     * @return array The data of the entities the user has permission to access.
     *
     * @throws OperationNotPermittedException If the user does not have permission to access any of the entities.
     */
    function __invoke(UserAuth $userAuth, string $entityName, array $ids = [], ?int $afterTimestamp = null): array
    {
        foreach ($ids as $id) {
            if ($this->accessValidatorRepository->canAccessEntity($userAuth,
                    new EntityReference($entityName, $id),
                    Permission::READ) === false) {
                throw new OperationNotPermittedException("No have access to entity $entityName with id $id");
            }
        }

        return $this->parseData(
            $this->entityRepository->searchEntities($userAuth->getEffectiveUserId(), $entityName, $ids, $afterTimestamp)
        );
    }
}