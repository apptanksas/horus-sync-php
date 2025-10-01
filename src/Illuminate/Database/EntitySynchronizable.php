<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\EntityType;
use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Util\StringUtil;
use AppTank\Horus\Horus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @internal Class EntitySynchronizable
 *
 * An abstract base class for entity synchronizable models. This class extends Laravel's `Model` class
 * and implements `IEntitySynchronizable` to provide common functionalities for entity synchronization.
 * It includes soft delete functionality and manages entity parameters and schema.
 *
 * @package AppTank\Horus\Illuminate\Database
 */
abstract class EntitySynchronizable extends Model implements IEntitySynchronizable
{
    use SoftDeletes;

    /**
     * Constant for the primary key attribute name.
     *
     * @var string
     */
    const string ATTR_ID = "id";

    /**
     * Constant for the sync deleted at attribute name.
     *
     * @var string
     */
    const string ATTR_SYNC_DELETED_AT = "sync_deleted_at";

    const DELETED_AT = self::ATTR_SYNC_DELETED_AT;

    /**
     * The name of the entity.
     *
     * @var string
     */
    public string $entityName;

    /**
     * The version number of the entity.
     *
     * @var int
     */
    public int $versionNumber;

    /**
     * An array of synchronization parameters.
     *
     * @var SyncParameter[]
     */
    protected array $parameters;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = self::ATTR_ID;

    /**
     * BaseSynchronizable constructor.
     *
     * Initializes the entity name, version number, and parameters. Sets up the fillable attributes and
     * connection settings for the model.
     *
     * @param array $attributes Attributes to be set on the model.
     */
    public function __construct(array $attributes = [])
    {
        $this->entityName = static::getEntityName();
        $this->versionNumber = static::getVersionNumber();
        $this->parameters = static::parameters();

        // Merge fillable attributes
        $this->fillable = array_merge($this->fillable, array_map(fn($parameter) => $parameter->name, $this->parameters));

        parent::__construct($attributes);

        $this->setTable(static::getTableName());

        // Set the connection name if it is not set
        if (!is_null($connection = Horus::getInstance()->getConnectionName()) && is_null($this->connection)) {
            $this->setConnection($connection);
        }
    }

    /**
     * Get the name of the column used for soft deletes.
     *
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return self::ATTR_SYNC_DELETED_AT;
    }

    /**
     * Get the schema for the entity.
     *
     * This method returns an array representing the schema of the entity, including entity name, type, attributes,
     * and the current version number.
     *
     * @return array
     */
    public static function schema(): array
    {
        $attributes = [];
        $class = get_called_class();

        /**
         * @var SyncParameter[] $parameters
         */
        $parameters = array_merge(static::baseParameters(), $class::parameters());
        $filterParameters = [self::ATTR_SYNC_DELETED_AT];

        foreach ($parameters as $parameter) {
            if (in_array($parameter->name, $filterParameters)) {
                continue;
            }

            $attribute = [];
            $attribute["name"] = $parameter->name;
            $attribute["version"] = $parameter->version;
            $attribute["type"] = StringUtil::snakeCase($parameter->type->value);
            $attribute["nullable"] = $parameter->isNullable;

            if ($parameter->type == SyncParameterType::CUSTOM) {
                $attribute["regex"] = $parameter->regex;
            }

            if ($parameter->linkedEntity !== null) {
                $attribute["linked_entity"] = $parameter->linkedEntity;
                $attribute["delete_on_cascade"] = $parameter->deleteOnCascade;
            }

            if ($parameter->type == SyncParameterType::ENUM) {
                $attribute["options"] = array_map(fn($item) => strval($item), $parameter->options);
            }

            if ($parameter->type == SyncParameterType::RELATION_ONE_OF_MANY || $parameter->type == SyncParameterType::RELATION_ONE_OF_ONE) {
                $attribute["related"] = array_map(fn($classRelated) => $classRelated::schema(), $parameter->related);
            }


            $attributes[] = $attribute;
        }

        $instanceClass = new $class();
        $entityType = ($instanceClass instanceof ReadableEntitySynchronizable) ? EntityType::READABLE : EntityType::WRITABLE;

        return [
            "entity" => $class::getEntityName(),
            "type" => $entityType->value,
            "attributes" => $attributes,
            "current_version" => $class::getVersionNumber()
        ];
    }

    /**
     * Get the column indexes for the entity.
     *
     * @return string[]
     */
    public static function getColumnIndexes(): array
    {
        return [];
    }

    /**
     * Get the ID attribute of the model.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->getAttribute(self::ATTR_ID);
    }


}
