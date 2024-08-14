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
abstract class EntitySynchronizable extends BaseSynchronizable implements IEntitySynchronizable
{

    const ATTR_SYNC_OWNER_ID = "sync_owner_id";
    const ATTR_SYNC_HASH = "sync_hash";
    const ATTR_SYNC_CREATED_AT = "sync_created_at";
    const ATTR_SYNC_UPDATED_AT = "sync_updated_at";


    public $timestamps = false;

    public $incrementing = false;


    public function __construct(array $attributes = [])
    {
        $this->parameters = static::parameters();
        $this->fillable = array_merge(array_map(fn($parameter) => $parameter->name, $this->parameters), [
            self::ATTR_ID,
            self::ATTR_SYNC_OWNER_ID,
            self::ATTR_SYNC_HASH,
            self::ATTR_SYNC_CREATED_AT,
            self::ATTR_SYNC_UPDATED_AT
        ]);

        parent::__construct($attributes);
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


    // ------------------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------------------


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