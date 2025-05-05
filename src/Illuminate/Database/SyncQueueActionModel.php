<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal Class SyncQueueActionModel
 *
 * Eloquent model for the `sync_queue_actions` table, representing queued actions for synchronization.
 * Provides methods for accessing and manipulating synchronization queue data.
 *
 * @package AppTank\Horus\Illuminate\Database
 */
final class SyncQueueActionModel extends Model
{
    // Table and column names
    const string TABLE_NAME = "sync_queue_actions";
    const string ATTR_ACTION = "action"; // Action made on the entity.
    const string ATTR_ENTITY = "entity"; // Entity name
    const string ATTR_ENTITY_ID = "entity_id"; // Entity Identifier.
    const string ATTR_DATA = "data"; // JSON Data of the entity.
    const string ATTR_ACTIONED_AT = "actioned_at"; // UTC Datetime when the action was made.
    const string ATTR_SYNCED_AT = "synced_at"; // UTC Datetime when the action was synced.
    const string FK_USER_ID = "user_id"; // User Identifier responsible for the action made.
    const string FK_OWNER_ID = "owner_id"; // User Identifier owner of the entity.

    protected $table = self::TABLE_NAME;
    public $timestamps = false;

    protected $fillable = [
        self::FK_USER_ID,
        self::FK_OWNER_ID,
        self::ATTR_ACTION,
        self::ATTR_ENTITY,
        self::ATTR_ENTITY_ID,
        self::ATTR_DATA,
        self::ATTR_ACTIONED_AT,
        self::ATTR_SYNCED_AT
    ];

    /**
     * Constructor to set the database connection if specified.
     *
     * @param array $attributes Initial attributes for the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (!is_null(Horus::getInstance()->getConnectionName())) {
            $this->setConnection(Horus::getInstance()->getConnectionName());
        }
    }

    /**
     * Convert model attributes to an array.
     *
     * @return array Converted attributes with timestamps in Unix format.
     */
    function toArray(): array
    {
        $data = parent::toArray();

        $data[self::ATTR_ACTIONED_AT] = Carbon::parse($data[self::ATTR_ACTIONED_AT], "UTC")->timestamp;
        $data[self::ATTR_SYNCED_AT] = Carbon::parse($data[self::ATTR_SYNCED_AT], "UTC")->timestamp;

        return $data;
    }

    // ----------------------------------------------
    // GETTERS
    // ----------------------------------------------

    /**
     * Get the action attribute.
     *
     * @return string Action made on the entity.
     */
    public function getAction(): string
    {
        return $this->getAttribute(self::ATTR_ACTION);
    }

    /**
     * Get the entity attribute.
     *
     * @return string Entity name.
     */
    public function getEntity(): string
    {
        return $this->getAttribute(self::ATTR_ENTITY);
    }

    /**
     * Get the entity ID attribute.
     *
     * @return string Entity Identifier.
     */
    public function getEntityId(): string
    {
        return $this->getAttribute(self::ATTR_ENTITY_ID);
    }

    /**
     * Get the data attribute as an array.
     *
     * @return array JSON Data of the entity.
     */
    public function getData(): array
    {
        return json_decode($this->getAttribute(self::ATTR_DATA), true);
    }

    /**
     * Get the actioned_at timestamp as a DateTimeImmutable instance.
     *
     * @return \DateTimeImmutable UTC Datetime when the action was made.
     */
    public function getActionedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable((new DateTimeUtil())->parseDatetime($this->getAttribute(self::ATTR_ACTIONED_AT)));
    }

    /**
     * Get the synced_at timestamp as a DateTimeImmutable instance.
     *
     * @return \DateTimeImmutable UTC Datetime when the action was synced.
     */
    public function getSyncedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable((new DateTimeUtil())->parseDatetime($this->getAttribute(self::ATTR_SYNCED_AT)));
    }

    /**
     * Get the user ID attribute.
     *
     * @return int|string User Identifier responsible for the action made.
     */
    public function getUserId(): int|string
    {
        return $this->getAttribute(self::FK_USER_ID);
    }

    /**
     * Get the owner ID attribute.
     *
     * @return int|string User Identifier owner of the entity.
     */
    public function getOwnerId(): int|string
    {
        return $this->getAttribute(self::FK_OWNER_ID);
    }
}
