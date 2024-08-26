<?php

namespace AppTank\Horus\Core\Auth;
/**
 * Class AccessLevel
 *
 * Represents a collection of permissions granted to a user or entity.
 * Provides methods to check, encode, and decode permissions, as well as to validate the access level.
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class AccessLevel
{
    /**
     * @param Permission[] $permissions
     */
    function __construct(
        public array $permissions,
    )
    {
        $this->validate();
    }

    /**
     * Creates a new AccessLevel instance with the specified permissions.
     *
     * @param Permission ...$permission Permissions to include in the access level.
     * @return self
     */
    static function new(Permission ...$permission): self
    {
        return new self($permission);
    }

    /**
     * Creates a new AccessLevel instance with all possible permissions.
     *
     * @return self
     */
    static function all(): self
    {
        return self::new(...Permission::cases());
    }

    /**
     * Checks if the specified permission is included in the access level.
     *
     * @param Permission $permission The permission to check.
     * @return bool True if the permission is included, false otherwise.
     */
    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Encodes the permissions into a string representation.
     *
     * @return string The encoded permissions.
     */
    public function encode(): string
    {
        return implode('', array_map(fn($permission) => $permission->value(), $this->permissions));
    }

    /**
     * Decodes a string representation of permissions into an AccessLevel instance.
     *
     * @param string $encoded The encoded permissions.
     * @return self The decoded AccessLevel instance.
     */
    public static function decode(string $encoded): self
    {
        $permissions = array_map(fn($value) => Permission::newInstance($value), str_split($encoded));
        return new self($permissions);
    }

    /**
     * Validates the permissions of the access level.
     *
     * @throws \InvalidArgumentException If validation fails.
     */
    private function validate(): void
    {
        if (count($this->permissions) === 0) {
            throw new \InvalidArgumentException('AccessLevel must have at least one permission');
        }

        $permissionsNames = array_map(fn($permission) => $permission->value, $this->permissions);

        if (count($this->permissions) !== count(array_unique($permissionsNames))) {
            throw new \InvalidArgumentException('AccessLevel must have unique permissions');
        }

        if (count($this->permissions) !== count(array_filter($this->permissions, fn($permission) => $permission instanceof Permission))) {
            throw new \InvalidArgumentException('AccessLevel must have only Permission instances');
        }

        if (count($this->permissions) > Permission::count()) {
            throw new \InvalidArgumentException('AccessLevel must have at most ' . Permission::count() . ' permissions');
        }
    }
}