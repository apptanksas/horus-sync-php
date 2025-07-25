<?php

namespace AppTank\Horus\Core\Repository;

use AppTank\Horus\Core\Auth\UserAuth;

/**
 * @internal Interface IGetDataEntitiesUseCase
 *
 * Represents a use case for retrieving data entities for a user, including their own entities 
 * and entities granted to them by others.
 *
 * @package AppTank\Horus\Core\Repository
 *
 * @author John Ospina
 * Year: 2024
 */
interface IGetDataEntitiesUseCase
{
    /**
     * Retrieves data entities for a user.
     *
     * @param UserAuth $userAuth The authenticated user.
     * @param int|null $afterTimestamp Optional timestamp to filter entities updated after this time.
     * @return array An array of parsed entity data.
     */
    function __invoke(UserAuth $userAuth, ?int $afterTimestamp = null): array;
} 