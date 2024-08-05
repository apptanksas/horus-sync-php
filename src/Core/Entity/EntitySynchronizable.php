<?php

namespace AppTank\Horus\Core\Entity;

use Illuminate\Support\Str;

/**
 * Class EntitySynchronizable
 * The methods getEntityName and getVersionNumber must be implemented in the child class and they are static because
 * they are called from the schema method without create instance.
 */
abstract class EntitySynchronizable
{
    public string $entityName;
    public int $versionNumber;

    /**
     * @var SyncParameter[]
     */
    protected array $parameters;


    public function __construct(
        array $parameters
    )
    {
        $this->versionNumber = self::getVersionNumber();
        $this->entityName = self::getVersionNumber();
        $this->parameters = $parameters;
    }

    /**
     * @return SyncParameter[]
     */
    protected static function baseParameters(): array
    {
        return [
            SyncParameter::createPrimaryKeyString("id", 1),
            SyncParameter::createInt("sync_owner_id", 1),
            SyncParameter::createString("sync_hash", 1),
            SyncParameter::createTimestamp("sync_created_at", 1),
            SyncParameter::createTimestamp("sync_updated_at", 1),
            SyncParameter::createTimestamp("sync_deleted_at", 1),
        ];
    }

    /**
     * @return SyncParameter[]
     */
    abstract protected static function parameters(): array;

    /**
     *  Get the entity name
     * @return string
     */
    abstract protected static function getEntityName(): string;

    /**
     * Get the version number
     * @return int
     */
    abstract protected static function getVersionNumber(): int;

    /**
     * @return SyncParameter[]
     */
    static function schema(): array
    {
        $attributes = [];
        /**
         * @var $class EntitySynchronizable
         */
        $class = get_called_class();
        $parameters = array_merge(self::baseParameters(), $class::parameters());

        foreach ($parameters as $parameter) {
            $attribute = [];
            $attribute["name"] = $parameter->name;
            $attribute["version"] = $parameter->version;
            $attribute["type"] = Str::snake(strtolower($parameter->type->name));

            if ($parameter->type == SyncParameterType::RELATION_ONE_TO_MANY) {
                $attribute["related"] = $parameter->classNameRelated::schema();
            }

            $attributes[] = $attribute;
        }

        return [
            "entity" => $class::getEntityName(),
            "attributes" => $attributes,
            "current_version" => $class::getVersionNumber()
        ];
    }
}