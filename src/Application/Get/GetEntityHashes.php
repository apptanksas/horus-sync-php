<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\EntityRepository;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

/**
 * @internal Class GetEntityHashes
 *
 * Retrieves the hash values of the specified entities for the authenticated user.
 * This class interacts with the EntityRepository to obtain the hashes.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class GetEntityHashes
{
    /**
     * GetEntityHashes constructor.
     *
     * @param EntityRepository $entityRepository Repository for accessing entity data.
     */
    function __construct(
        private EntityRepository                $entityRepository,
        private EntityAccessValidatorRepository $accessValidatorRepository,
        private EntityMapper                    $entityMapper,
    )
    {

    }

    /**
     * Invokes the GetEntityHashes class to retrieve entity hashes.
     *
     * Fetches the hash values for entities associated with the authenticated user.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param string $entityName The name of the entity to retrieve hashes for.
     * @return array An array of entity hashes.
     */
    function __invoke(UserAuth $userAuth, string $entityName): array
    {
        $userIds = array_merge([$userAuth->userId], $userAuth->getUserOwnersId());
        $result = $this->entityRepository->getEntityHashes($userIds, $entityName);

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
}
