<?php

namespace AppTank\Horus\Core\Trait;


trait EnumIterator
{

    /**
    /**
     * @param string|int $value
     * @return \UnitEnum
     */
    public static function newInstance(string|int $value): self
    {
        return array_merge([], array_filter(self::cases(),
            fn($status) => strval($status->value ?? $status->name) == strval($value) || $status->name == strval($value)
        ))[0];
    }

    /**
     * @return string[]|int[]
     */
    public static function getValues(): array
    {
        return array_map(function ($status) {
            $value = $status->value ?? $status->name;
            return (is_numeric($value)) ? intval($value) : (string)$value;
        }, self::cases());
    }

    public function value(): string
    {
        return strval($this->value ?? $this->name);
    }

    public static function random(): self
    {
        return self::cases()[rand(0, count(self::cases()) - 1)];
    }

    public static function exists(string|int $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    public static function isNotExists(string|int $value): bool
    {
        return self::tryFrom($value) === null;
    }
}

