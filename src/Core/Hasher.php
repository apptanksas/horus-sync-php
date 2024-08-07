<?php

namespace AppTank\Horus\Core;

class Hasher
{
    const ALGORITHM = "sha256";

    public static function hash(array $data): string
    {
        // Validate if array keys are numeric
        if (count(array_filter(array_keys($data), 'is_int')) > 0) {
            // Set values as keys
            $data = array_combine(array_values($data), array_values($data));
        }

        // Validate is data are primitive types
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                throw new \InvalidArgumentException("Data key [$value] must be primitive types");
            }
        }

        // Sort the array by key
        ksort($data);

        return hash(self::ALGORITHM, join("", $data));
    }

    public static function getHashLength(): int
    {
        return strlen(hash(self::ALGORITHM, "abc123"));
    }
}