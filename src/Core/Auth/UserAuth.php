<?php

namespace AppTank\Horus\Core\Auth;

readonly class UserAuth
{
    /**
     * @param string|int $userId
     * @param EntityGranted[] $entityGrants
     */
    function __construct(
        public string|int    $userId,
        public array         $entityGrants = [],
        public ?UserActingAs $userActingAs = null
    )
    {

    }

    function getUserOwnersId(): array
    {
        return array_unique(array_map(fn($item) => $item->userOwnerId, $this->entityGrants));
    }

    function getEntitiesNameGranted(): array
    {
        return array_unique(array_map(fn($item) => $item->entityReference->entityName, $this->entityGrants));
    }

    function hasGranted(string $entityName, string $entityId, Permission $permission): bool
    {
        foreach ($this->entityGrants as $entityGrant) {
            if ($entityGrant->entityReference->entityName === $entityName &&
                $entityGrant->entityReference->entityId === $entityId &&
                $entityGrant->accessLevel->can($permission)) {
                return true;
            }
        }
        return false;
    }

    function hasNotGranted(string $entityName, string $entityId): bool
    {
        return !$this->hasGranted($entityName, $entityId);
    }

    function getEffectiveUserId(): string|int
    {
        return $this->userActingAs?->userId ?? $this->userId;
    }

    function isActingAs(): bool
    {
        return !is_null($this->userActingAs);
    }
}