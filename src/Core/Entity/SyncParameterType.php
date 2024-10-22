<?php

namespace AppTank\Horus\Core\Entity;

/**
 * Enum SyncParameterType
 *
 * Defines the various types of synchronization parameters used in the system.
 * This includes types for primary keys, data types, and relationships.
 *
 * @package AppTank\Horus\Core\Entity
 *
 * @author John Ospina
 * Year: 2024
 */
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

    case REFERENCE_FILE = "ref_file";

    case RELATION_ONE_OF_MANY = "relation_one_of_many";
    case RELATION_ONE_OF_ONE = "relation_one_of_one";

    /**
     * Checks if the parameter type is not a relation type.
     *
     * @return bool True if the type is not a relation, false otherwise.
     */
    public function isNotRelation(): bool
    {
        return $this !== self::RELATION_ONE_OF_MANY && $this !== self::RELATION_ONE_OF_ONE;
    }

    /**
     * Checks if the parameter type is a relation type.
     *
     * @return bool True if the type is a relation, false otherwise.
     */
    public function isRelation(): bool
    {
        return $this === self::RELATION_ONE_OF_MANY || $this === self::RELATION_ONE_OF_ONE;
    }
}
