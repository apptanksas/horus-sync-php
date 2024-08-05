<?php

namespace AppTank\Horus\Domain\Entity;


class SyncParameter
{
    public function __construct(
        public string            $name,
        public SyncParameterType $type,
        public int               $version = 1,
        public ?string           $classNameRelated = null
    )
    {
        if ($this->classNameRelated != null && !class_exists($this->classNameRelated)) {
            throw new \InvalidArgumentException("ClassName related [$classNameRelated] not exists!");
        }
    }

    public static function createPrimaryKeyInteger(string $name, int $version)
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_INTEGER, $version);
    }

    public static function createPrimaryKeyString(string $name, int $version)
    {
        return new SyncParameter($name, SyncParameterType::PRIMARY_KEY_STRING, $version);
    }

    public static function createInt(string $name, int $version)
    {
        return new SyncParameter($name, SyncParameterType::INT, $version);
    }

    public static function createFloat(string $name, int $version)
    {
        return new SyncParameter($name, SyncParameterType::FLOAT, $version);
    }

    public static function createString(string $name, int $version)
    {
        return new SyncParameter($name, SyncParameterType::STRING, $version);
    }

    public static function createTimestamp(string $name, int $version)
    {
        return new SyncParameter($name, SyncParameterType::TIMESTAMP, $version);
    }

    public static function createRelationOneToMany(string $name, string $className, int $version)
    {
        return new SyncParameter($name, SyncParameterType::RELATION_ONE_TO_MANY, $version, $className);
    }
}
