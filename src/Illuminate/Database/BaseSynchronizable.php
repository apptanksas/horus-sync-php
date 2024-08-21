<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\EntityType;
use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Core\Entity\SyncParameterType;
use AppTank\Horus\Core\Util\StringUtil;
use AppTank\Horus\HorusContainer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseSynchronizable extends Model implements IEntitySynchronizable
{
    use SoftDeletes;

    const string ATTR_ID = "id";

    const string ATTR_SYNC_DELETED_AT = "sync_deleted_at";

    public string $entityName;
    public int $versionNumber;

    /**
     * @var SyncParameter[]
     */
    protected array $parameters;

    protected $primaryKey = self::ATTR_ID;


    public function __construct(array $attributes = [])
    {
        $this->entityName = static::getEntityName();
        $this->versionNumber = static::getVersionNumber();
        $this->parameters = static::parameters();

        $this->fillable = array_merge($this->fillable, array_map(fn($parameter) => $parameter->name, $this->parameters));

        parent::__construct($attributes);

        $this->setTable(static::getTableName());

        // Set the connection name if it is not set
        if (!is_null($connection = HorusContainer::getInstance()->getConnectionName()) && is_null($this->connection)) {
            $this->setConnection($connection);
        }
    }

    public function getDeletedAtColumn(): string
    {
        return self::ATTR_SYNC_DELETED_AT;
    }

    public static function schema(): array
    {
        $attributes = [];
        /**
         * @var $class IEntitySynchronizable
         */
        $class = get_called_class();
        $parameters = array_merge(static::baseParameters(), $class::parameters());
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

        $instanceClass = new $class();
        $entityType = ($instanceClass instanceof LookupSynchronizable) ? EntityType::LOOKUP : EntityType::EDITABLE;

        return [
            "entity" => $class::getEntityName(),
            "type" => $entityType->value,
            "attributes" => $attributes,
            "current_version" => $class::getVersionNumber()
        ];
    }

    public function getId(): string
    {
        return $this->getAttribute(self::ATTR_ID);
    }
}