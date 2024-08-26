<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Repository\EntityRepository;

/**
 * @internal Class GetEntityHashes
 *
 * Retrieves the hash values of the specified entities for the authenticated user.
 * This class interacts with the EntityRepository to obtain the hashes.
 *
 * Author: John Ospina
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
        private EntityRepository $entityRepository
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
        return $this->entityRepository->getEntityHashes($userAuth->getEffectiveUserId(), $entityName);
    }
}
