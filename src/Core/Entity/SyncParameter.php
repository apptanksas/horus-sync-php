<?php

namespace AppTank\Horus\Core\Entity;


class SyncParameter
{
    /**
     * @param string $name
     * @param SyncParameterType $type
     * @param int $version
     * @param bool $isNullable
     * @param array|string[] $related
     */
    public function __construct(
        public string            $name,
        public SyncParameterType $type,
        public int               $version = 1,
        public bool              $isNullable = false,
        public array             $related = []
    )
    {
        $this->validateRelated();
    }

    public static function createPrimaryKeyInteger(string $name, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_INTEGER, $version, false);
    }

    public static function createPrimaryKeyString(string $name, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_STRING, $version, false);
    }

    public static function createPrimaryKeyUUID(string $name, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_UUID, $version, false);
    }

    public static function createInt(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::INT, $version, $isNullable);
    }

    public static function createFloat(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::FLOAT, $version, $isNullable);
    }

    public static function createBoolean(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::BOOLEAN, $version, $isNullable);
    }

    public static function createString(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::STRING, $version, $isNullable);
    }

    public static function createTimestamp(string $name, int $version, bool $isNullable = false): self
    {
        return new SyncParameter($name, SyncParameterType::TIMESTAMP, $version, $isNullable);
    }

    public static function createRelationOneToMany(string $name, array $relatedClass, int $version): self
    {
        return new SyncParameter($name, SyncParameterType::RELATION_ONE_TO_MANY, $version, false, $relatedClass);
    }

    // ------------------------------------------------------------------------
    // Validations
    // ------------------------------------------------------------------------

    private function validateRelated(): void
    {
        foreach ($this->related as $related) {
            if ($related != null && !class_exists($related)) {
                throw new \InvalidArgumentException("ClassName related [$related] not exists!");
            }
        }
    }

}
