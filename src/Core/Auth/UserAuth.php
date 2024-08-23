<?php

namespace AppTank\Horus\Core\Auth;

readonly class UserAuth
{
    /**
     * @param string|int $userId
     * @param EntityGranted[] $entityGrants
     */
    function __construct(
        public string|int $userId,
        public array      $entityGrants = []
    )
    {

    }

    function eachUserGranted(callable $callback): void
    {
        $callback($this->userId);
        foreach ($this->entityGrants as $entityGranted) {
            $callback($entityGranted->userOwnerId);
        }
    }

    function groupByEachUserGranted(callable $callback): array
    {
        $output = $callback($this->userId, null);
        foreach ($this->entityGrants as $entityGranted) {
            $output = array_merge($output, $callback($entityGranted->userOwnerId));
        }

        return $output;
    }
}