<?php

namespace AppTank\Horus\Core\Auth;

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

    static function new(Permission ...$permission): self
    {
        return new self($permission);
    }

    static function all(): self
    {
        return self::new(...Permission::cases());
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function encode(): string
    {
        return implode('', array_map(fn($permission) => $permission->value(), $this->permissions));
    }

    public static function decode(string $encoded): self
    {
        $permissions = array_map(fn($value) => Permission::newInstance($value), str_split($encoded));
        return new self($permissions);
    }

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