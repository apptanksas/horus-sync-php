<?php

namespace AppTank\Horus\Domain\Util;

class StringUtil
{
    public static function snakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}