<?php

namespace AppTank\Horus\Core\Auth;

/**
 * Class UserAuth
 *
 * Represents the authentication details of a user. This includes the user ID, entity grants, and optional user acting as another user.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class UserAuth
{
    /**
     * @param string|int $userId The ID of the user.
     * @param EntityGranted[] $entityGrants List of entity grants associated with the user.
     * @param ?UserActingAs $userActingAs Optional instance of UserActingAs if the user is acting as another user.
     */
    function __construct(
        public string|int    $userId,
        public array         $entityGrants = [],
        public ?UserActingAs $userActingAs = null
    )
    {

    }

    /**
     * Get unique IDs of users who own entities granted to this user.
     *
     * @return array Array of unique user IDs.
     */
    function getUserOwnersId(): array
    {
        return array_unique(array_map(fn($item) => $item->userOwnerId, $this->entityGrants));
    }

    /**
     * Get unique names of entities granted to this user.
     *
     * @return array Array of unique entity names.
     */
    function getEntitiesNameGranted(): array
    {
        return array_unique(array_map(fn($item) => $item->entityReference->entityName, $this->entityGrants));
    }

    /**
     * Check if the user has been granted a specific permission for a given entity.
     *
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     * @param Permission $permission The permission to check.
     * @return bool True if granted, otherwise false.
     */
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

    /**
     * Check if the user has not been granted access to a specific entity.
     *
     * @param string $entityName The name of the entity.
     * @param string $entityId The ID of the entity.
     * @param Permission $permission The permission to check.
     * @return bool True if not granted, otherwise false.
     */
    function hasNotGranted(string $entityName, string $entityId, Permission $permission): bool
    {
        return !$this->hasGranted($entityName, $entityId, $permission);
    }

    /**
     * Get the effective user ID, considering if the user is acting as another user.
     *
     * @return string|int The effective user ID.
     */
    function getEffectiveUserId(): string|int
    {
        return $this->userActingAs?->userId ?? $this->userId;
    }

    /**
     * Check if the user is acting as another user.
     *
     * @return bool True if acting as another user, otherwise false.
     */
    function isActingAs(): bool
    {
        return !is_null($this->userActingAs);
    }


    function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'entityGrants' => array_map(fn($grant) => $grant->toArray(), $this->entityGrants),
            'userActingAs' => $this->userActingAs?->userId,
            "effectiveUserId" => $this->getEffectiveUserId()
        ];
    }
}