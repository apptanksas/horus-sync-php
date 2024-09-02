<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\Entity\IEntitySynchronizable;
use AppTank\Horus\Core\Entity\SyncParameter;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;

/**
 * Class EntitySynchronizable
 *
 * This abstract class extends `BaseSynchronizable` and implements `IEntitySynchronizable`.
 * It provides default implementations for managing entity synchronization with additional attributes
 * and methods. The `getEntityName` and `getVersionNumber` methods must be implemented in child classes
 * and are static because they are called from the `schema` method without creating an instance.
 *
 * @package AppTank\Horus\Illuminate\Database
 */
abstract class EntitySynchronizable extends BaseSynchronizable implements IEntitySynchronizable
{
    public const ATTR_SYNC_OWNER_ID = "sync_owner_id";
    public const ATTR_SYNC_HASH = "sync_hash";
    public const ATTR_SYNC_CREATED_AT = "sync_created_at";
    public const ATTR_SYNC_UPDATED_AT = "sync_updated_at";

    public $timestamps = false;
    public $incrementing = false;

    /**
     * EntitySynchronizable constructor.
     *
     * Initializes entity parameters and fillable attributes.
     *
     * @param array $attributes Attributes to be set on the model.
     */
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
     * Get the base synchronization parameters.
     *
     * @return SyncParameter[] List of base synchronization parameters.
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

    /**
     * Get the table name for the entity.
     *
     * @return string Table name for the entity.
     */
    final public static function getTableName(): string
    {
        return "se_" . static::getEntityName();
    }

    // ------------------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------------------

    /**
     * Get the owner ID of the entity.
     *
     * @return string|int Owner ID.
     */
    public function getOwnerId(): string|int
    {
        return $this->getAttribute(self::ATTR_SYNC_OWNER_ID);
    }

    /**
     * Get the hash of the entity.
     *
     * @return string Hash.
     */
    public function getHash(): string
    {
        return $this->getAttribute(self::ATTR_SYNC_HASH);
    }

    /**
     * Get the updated timestamp of the entity.
     *
     * @return \DateTimeImmutable Updated timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable((new DateTimeUtil())->parseDatetime($this->getAttribute(self::ATTR_SYNC_UPDATED_AT)));
    }

    /**
     * Get the created timestamp of the entity.
     *
     * @return int Created timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable((new DateTimeUtil())->parseDatetime($this->getAttribute(self::ATTR_SYNC_CREATED_AT)));
    }

    // ------------------------------------------------------------------------
    // OVERRIDE
    // ------------------------------------------------------------------------

    /**
     * Save the entity to the database.
     *
     * Sets creation and update timestamps if they are not already set.
     *
     * @param array $options Options for the save operation.
     * @return bool True if the save was successful, otherwise false.
     */
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
     * Get one-to-many relation methods.
     *
     * @return string[] List of one-to-many relation methods.
     */
    public function getRelationsOneOfMany(): array
    {
        return [];
    }

    /**
     * Get one-to-one relation methods.
     *
     * @return string[] List of one-to-one relation methods.
     */
    public function getRelationsOneOfOne(): array
    {
        return [];
    }

    // ------------------------------------------------------------------------
    // PERMISSIONS
    // ------------------------------------------------------------------------

    /**
     * Check if a user is the owner of the entity.
     *
     * @param string $entityId Entity ID.
     * @param string|int $userId User ID.
     * @return bool True if the user is the owner, otherwise false.
     */
    public static function isOwner(string $entityId, string|int $userId): bool
    {
        $class = get_called_class();
        return $class::where(self::ATTR_SYNC_OWNER_ID, $userId)->where(self::ATTR_ID, $entityId)->exists();
    }
}
