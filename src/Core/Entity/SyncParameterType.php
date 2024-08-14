<?php

namespace AppTank\Horus\Core\Entity;

enum SyncParameterType: string
{
    case PRIMARY_KEY_INTEGER = "primary_key_integer";
    case PRIMARY_KEY_STRING = "primary_key_string";
    case PRIMARY_KEY_UUID = "primary_key_uuid";

    case INT = "int";
    case FLOAT = "float";
    case BOOLEAN = "boolean";
    case STRING = "string";
    case TEXT = "text";
    case JSON = "json";
    case ENUM = "enum";
    case TIMESTAMP = "timestamp";
    case UUID = "uuid";

    case RELATION_ONE_OF_MANY = "relation_one_of_many";
    case RELATION_ONE_OF_ONE = "relation_one_of_one";

    public function isNotRelation(): bool
    {
        return $this !== self::RELATION_ONE_OF_MANY && $this !== self::RELATION_ONE_OF_ONE;
    }

    public function isRelation(): bool
    {
        return $this === self::RELATION_ONE_OF_MANY || $this === self::RELATION_ONE_OF_ONE;
    }
}
