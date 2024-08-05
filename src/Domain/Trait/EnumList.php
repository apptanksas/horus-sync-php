<?php

namespace AppTank\Horus\Domain\Trait;

trait EnumList
{
    /**
     * @param string $value
     * @return \UnitEnum
     */
    public static function getValue(string $value): self
    {
        return array_merge([], array_filter(self::cases(), fn($status) => $status->name == $value))[0];
    }

    /**
     * @return string[]
     */
    public static function getValues(): array
    {
        return array_map(fn($status) => $status->name, self::cases());
    }
}
