<?php

namespace AppTank\Horus\Core\Entity;

use AppTank\Horus\Core\Util\StringUtil;

/**
 * Class EntitySynchronizable
 * The methods getEntityName and getVersionNumber must be implemented in the child class and they are static because
 * they are called from the schema method without create instance.
 */
abstract class EntitySynchronizable
{

    const PARAM_ID = "id";
    const PARAM_SYNC_OWNER_ID = "sync_owner_id";
    const PARAM_SYNC_HASH = "sync_hash";
    const PARAM_SYNC_CREATED_AT = "sync_created_at";
    const PARAM_SYNC_UPDATED_AT = "sync_updated_at";
    const PARAM_SYNC_DELETED_AT = "sync_deleted_at";

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
            SyncParameter::createPrimaryKeyString(self::PARAM_ID, 1),
            SyncParameter::createString(self::PARAM_SYNC_OWNER_ID, 1),
            SyncParameter::createString(self::PARAM_SYNC_HASH, 1),
            SyncParameter::createTimestamp(self::PARAM_SYNC_CREATED_AT, 1),
            SyncParameter::createTimestamp(self::PARAM_SYNC_UPDATED_AT, 1),
            SyncParameter::createTimestamp(self::PARAM_SYNC_DELETED_AT, 1),
        ];
    }

    /**
     * @return SyncParameter[]
     */
    abstract public static function parameters(): array;

    /**
     *  Get the entity name
     * @return string
     */
    abstract public static function getEntityName(): string;

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
        $filterParameters = [self::PARAM_SYNC_DELETED_AT];

        /**
         * @var $parameter SyncParameter
         */
        foreach ($parameters as $parameter) {

            if (in_array($parameter->name, $filterParameters)) {
                continue;
            }

            $attribute = [];
            $attribute["name"] = $parameter->name;
            $attribute["version"] = $parameter->version;
            $attribute["type"] = StringUtil::snakeCase($parameter->type->value);
            $attribute["nullable"] = $parameter->isNullable;

            if ($parameter->type == SyncParameterType::RELATION_ONE_TO_MANY) {
                $attribute["related"] = array_map(fn($classRelated) => $classRelated::schema(), $parameter->related);
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