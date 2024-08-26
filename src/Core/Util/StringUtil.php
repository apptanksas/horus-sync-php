<?php

namespace AppTank\Horus\Core\Util;

/**
 * @internal Class StringUtil
 *
 * Provides utility functions for string manipulation.
 *
 * @package AppTank\Horus\Core\Util
 */
class StringUtil
{
    /**
     * Converts a given string to snake_case.
     *
     * This method transforms a string from camelCase or PascalCase to snake_case.
     * For example, "CamelCaseString" will be converted to "camel_case_string".
     *
     * @param string $input The input string to be converted.
     * @return string The converted string in snake_case.
     */
    public static function snakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
