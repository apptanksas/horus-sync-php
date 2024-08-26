<?php

namespace AppTank\Horus\Core\Trait;

use UnitEnum;

/**
 * @internal Trait EnumIterator
 *
 * Provides utility methods for working with enum types in PHP. This trait can be used
 * with enum classes to add functionalities such as creating new instances from values,
 * retrieving all possible values, checking existence, and more.
 *
 * @package AppTank\Horus\Core\Trait
 */
trait EnumIterator
{
    /**
     * Creates a new instance of the enum based on the provided value.
     *
     * @param string|int $value The value of the enum to create an instance of.
     * @return \UnitEnum The created enum instance.
     * @throws \InvalidArgumentException If the value does not match any enum case.
     */
    public static function newInstance(string|int $value): self
    {
        return array_merge([], array_filter(self::cases(),
            fn($status) => strval($status->value ?? $status->name) == strval($value) || $status->name == strval($value)
        ))[0];
    }

    /**
     * Retrieves all possible values of the enum.
     *
     * @return string[]|int[] An array of all possible values of the enum.
     */
    public static function getValues(): array
    {
        return array_map(function ($status) {
            $value = $status->value ?? $status->name;
            return (is_numeric($value)) ? intval($value) : (string)$value;
        }, self::cases());
    }

    /**
     * Returns the number of cases in the enum.
     *
     * @return int The number of cases.
     */
    public static function count(): int
    {
        return count(self::cases());
    }

    /**
     * Retrieves the value of the current enum instance.
     *
     * @return string The value of the current enum instance.
     */
    public function value(): string
    {
        return strval($this->value ?? $this->name);
    }

    /**
     * Selects a random case from the enum.
     *
     * @return self A random enum instance.
     */
    public static function random(): self
    {
        return self::cases()[rand(0, count(self::cases()) - 1)];
    }

    /**
     * Checks if the provided value exists in the enum.
     *
     * @param string|int $value The value to check for existence.
     * @return bool True if the value exists, false otherwise.
     */
    public static function exists(string|int $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Checks if the provided value does not exist in the enum.
     *
     * @param string|int $value The value to check for non-existence.
     * @return bool True if the value does not exist, false otherwise.
     */
    public static function isNotExists(string|int $value): bool
    {
        return self::tryFrom($value) === null;
    }
}
