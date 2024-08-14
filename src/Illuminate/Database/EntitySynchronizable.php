<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Util\StringUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EntitySynchronizable
 * The methods getEntityName and getVersionNumber must be implemented in the child class and they are static because
 * they are called from the schema method without create instance.
 */
abstract class EntitySynchronizable extends Model implements IEntitySynchronizable
{

    use SoftDeletes;

    const ATTR_ID = "id";
    const ATTR_SYNC_OWNER_ID = "sync_owner_id";
    const ATTR_SYNC_HASH = "sync_hash";
    const ATTR_SYNC_CREATED_AT = "sync_created_at";
    const ATTR_SYNC_UPDATED_AT = "sync_updated_at";
    const ATTR_SYNC_DELETED_AT = "sync_deleted_at";

    public string $entityName;
    public int $versionNumber;

    /**
     * @var SyncParameter[]
     */
    protected array $parameters;

    public $timestamps = false;

    public $incrementing = false;
    protected $primaryKey = self::ATTR_ID;

    public function __construct(array $attributes = [])
    {
        $this->entityName = static::getEntityName();
        $this->versionNumber = static::getVersionNumber();
        $this->parameters = static::parameters();
        $this->fillable = array_merge(array_map(fn($parameter) => $parameter->name, $this->parameters), [
            self::ATTR_ID,
            self::ATTR_SYNC_OWNER_ID,
            self::ATTR_SYNC_HASH,
            self::ATTR_SYNC_CREATED_AT,
            self::ATTR_SYNC_UPDATED_AT
        ]);

        parent::__construct($attributes);


        $this->setTable(static::getTableName());
    }

    public function getDeletedAtColumn(): string
    {
        return self::ATTR_SYNC_DELETED_AT;
    }

    /**
     * @return SyncParameter[]
     */
    public static function baseParameters(): array
    {
        return [
            SyncParameter::createPrimaryKeyString(self::ATTR_ID, 1),
            SyncParameter::createString(self::ATTR_SYNC_OWNER_ID, 1),
            SyncParameter::createString(self::ATTR_SYNC_HASH, 1),
            SyncParameter::createTimestamp(self::ATTR_SYNC_CREATED_AT, 1),
            SyncParameter::createTimestamp(self::ATTR_SYNC_UPDATED_AT, 1),
            SyncParameter::createTimestamp(self::ATTR_SYNC_DELETED_AT, 1),
        ];
    }


    final public static function getTableName(): string
    {
        return "se_" . static::getEntityName();
    }

    /**
     * @return SyncParameter[]
     */
    public static function schema(): array
    {
        $attributes = [];
        /**
         * @var $class IEntitySynchronizable
         */
        $class = get_called_class();
        $parameters = array_merge(self::baseParameters(), $class::parameters());
        $filterParameters = [self::ATTR_SYNC_DELETED_AT];

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

            if ($parameter->type == SyncParameterType::RELATION_ONE_OF_MANY || $parameter->type == SyncParameterType::RELATION_ONE_OF_ONE) {
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

    // ------------------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------------------

    public function getId(): string
    {
        return $this->getAttribute(self::ATTR_ID);
    }

    public function getOwnerId(): string|int
    {
        return $this->getAttribute(self::ATTR_SYNC_OWNER_ID);
    }

    public function getHash(): string
    {
        return $this->getAttribute(self::ATTR_SYNC_HASH);
    }

    public function getUpdatedAt(): int
    {
        return $this->getAttribute(self::ATTR_SYNC_UPDATED_AT);
    }

    public function getCreatedAt(): int
    {
        return $this->getAttribute(self::ATTR_SYNC_CREATED_AT);
    }

    // ------------------------------------------------------------------------
    // OVERRIDE
    // ------------------------------------------------------------------------

    function save(array $options = []): bool
    {
        if (!$this->exists && !$this->getAttribute(self::ATTR_SYNC_CREATED_AT)) {
            $this->setAttribute(self::ATTR_SYNC_CREATED_AT, time());
        }

        if (!$this->exists && !$this->getAttribute(self::ATTR_SYNC_UPDATED_AT)) {
            $this->setAttribute(self::ATTR_SYNC_UPDATED_AT, time());
        }

        return parent::save($options);
    }


    // ------------------------------------------------------------------------
    // RELATIONS
    // ------------------------------------------------------------------------

    /**
     * Get relations methods many
     * @return string[]
     */
    public function getRelationsOneOfMany(): array
    {
        return [];
    }

    /**
     * Get relations methods one
     * @return string[]
     */
    public function getRelationsOneOfOne(): array
    {
        return [];
    }
}