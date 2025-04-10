<?php

namespace AppTank\Horus\Core\Entity;

use AppTank\Horus\Core\Exception\ClientException;

/**
 * Class SyncParameter
 *
 * Represents a synchronization parameter used for defining the structure of synchronized entities.
 * It includes various static methods to create parameters of different types, such as integers, strings, and UUIDs.
 *
 * @package AppTank\Horus\Core\Entity
 *
 * @author John Ospina
 * Year: 2024
 */
class SyncParameter
{
    /**
     * @param string $name The name of the parameter.
     * @param SyncParameterType $type The type of the parameter.
     * @param int $version The version of the parameter (default is 1).
     * @param bool $isNullable Indicates if the parameter is nullable (default is false).
     * @param array|string[] $related An array of related class names (default is an empty array).
     * @param array $options Additional options for the parameter (default is an empty array).
     * @param string|null $linkedEntity The name of the linked entity (default is null).
     * @param bool $deleteOnCascade Indicates if the parameter should be deleted on cascade (default is true).
     */
    public function __construct(
        public string            $name,
        public SyncParameterType $type,
        public int               $version = 1,
        public bool              $isNullable = false,
        public array             $related = [],
        public array             $options = [],
        public ?string           $linkedEntity = null,
        public bool              $deleteOnCascade = true
    )
    {
        $this->validateRelated();
    }

    /**
     * Creates a primary key parameter of integer type.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @return self A new instance of SyncParameter.
     */
    public static function createPrimaryKeyInteger(string $name, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_INTEGER, $version, false);
    }

    /**
     * Creates a primary key parameter of string type.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @return self A new instance of SyncParameter.
     */
    public static function createPrimaryKeyString(string $name, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_STRING, $version, false);
    }

    /**
     * Creates a primary key parameter of UUID type.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @return self A new instance of SyncParameter.
     */
    public static function createPrimaryKeyUUID(string $name, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_UUID, $version, false);
    }

    /**
     * Creates an integer parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createInt(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::INT, $version, $isNullable);
    }

    /**
     * Creates a float parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createFloat(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::FLOAT, $version, $isNullable);
    }

    /**
     * Creates a boolean parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createBoolean(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::BOOLEAN, $version, $isNullable);
    }

    /**
     * Creates a string parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createString(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::STRING, $version, $isNullable);
    }

    /**
     * Creates a text parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createText(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::TEXT, $version, $isNullable);
    }

    /**
     * Creates a timestamp parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createTimestamp(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::TIMESTAMP, $version, $isNullable);
    }

    /**
     * Creates an enum parameter.
     *
     * @param string $name The name of the parameter.
     * @param array $options The options for the enum.
     * @param int $version The version of the parameter.
     * @return self A new instance of SyncParameter.
     */
    public static function createEnum(string $name, array $options, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::ENUM, $version, false, [], $options);
    }

    /**
     * Creates a UUID parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createUUID(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::UUID, $version, $isNullable);
    }

    /**
     * Creates a JSON parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createJSON(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::JSON, $version, $isNullable);
    }

    /**
     * Creates a reference file parameter.
     *
     * @param string $name The name of the parameter.
     * @param int $version The version of the parameter.
     * @param bool $isNullable Indicates if the parameter is nullable.
     * @return self A new instance of SyncParameter.
     */
    public static function createReferenceFile(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::REFERENCE_FILE, $version, $isNullable);
    }

    /**
     * Creates a relation parameter for one-to-many relationships.
     *
     * @param array $relatedClass An array of related class names.
     * @param int $version The version of the parameter.
     * @return self A new instance of SyncParameter.
     */
    public static function createRelationOneOfMany(array $relatedClass, int $version): self
    {
        return new SyncParameter("relations_one_of_many", SyncParameterType::RELATION_ONE_OF_MANY, $version, false, $relatedClass);
    }

    /**
     * Creates a relation parameter for one-to-one relationships.
     *
     * @param array $relatedClass An array of related class names.
     * @param int $version The version of the parameter.
     * @return self A new instance of SyncParameter.
     */
    public static function createRelationOneOfOne(array $relatedClass, int $version): self
    {
        return new SyncParameter("relations_one_of_one", SyncParameterType::RELATION_ONE_OF_ONE, $version, false, $relatedClass);
    }

    /**
     * Creates a UUID foreign key parameter.
     *
     * @param string $name
     * @param int $version
     * @param string $linkedEntity
     * @return self
     */
    public static function createUUIDForeignKey(string $name, int $version, string $linkedEntity): self
    {
        return new SyncParameter($name, SyncParameterType::UUID, $version, linkedEntity: $linkedEntity);
    }

    /**
     * Creates a string foreign key parameter.
     *
     * @param string $name
     * @param int $version
     * @param string $linkedEntity
     * @return self
     */
    public static function createStringForeignKey(string $name, int $version, string $linkedEntity): self
    {
        return new SyncParameter($name, SyncParameterType::STRING, $version, linkedEntity: $linkedEntity);
    }

    /**
     * Creates an integer foreign key parameter.
     *
     * @param string $name
     * @param int $version
     * @param string $linkedEntity
     * @return self
     */
    public static function createIntForeignKey(string $name, int $version, string $linkedEntity): self
    {
        return new SyncParameter($name, SyncParameterType::INT, $version, linkedEntity: $linkedEntity);
    }

    // ------------------------------------------------------------------------
    // Validations
    // ------------------------------------------------------------------------

    /**
     * Validates that all related classes exist.
     *
     * @return void
     * @throws ClientException If a related class does not exist.
     */
    private function validateRelated(): void
    {
        foreach ($this->related as $related) {
            if ($related != null && !class_exists($related)) {
                throw new ClientException("ClassName related [$related] not exists!");
            }
        }
    }
}
