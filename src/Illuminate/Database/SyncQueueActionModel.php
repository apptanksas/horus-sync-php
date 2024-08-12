<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\HorusContainer;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;

final class SyncQueueActionModel extends Model
{

    const TABLE_NAME = "sync_queue_actions";


    const ATTR_ACTION = "action"; // Action made on the entity.
    const ATTR_ENTITY = "entity"; // Entity name
    const ATTR_DATA = "data"; // JSON Data of the entity.
    const ATTR_ACTIONED_AT = "actioned_at"; // UTC Datetime when the action was made.
    const ATTR_SYNCED_AT = "synced_at"; // UTC Datetime when the action was synced.
    const FK_USER_ID = "user_id"; // User Identifier responsible for the action made.

    const FK_OWNER_ID = "owner_id"; // User Identifier owner of the entity.

    protected $table = self::TABLE_NAME;

    public $timestamps = false;


    protected $fillable = [self::FK_USER_ID, self::FK_OWNER_ID, self::ATTR_ACTION, self::ATTR_ENTITY, self::ATTR_DATA, self::ATTR_ACTIONED_AT, self::ATTR_SYNCED_AT];


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (!is_null(HorusContainer::getInstance()->getConnectionName()))
            $this->setConnection(HorusContainer::getInstance()->getConnectionName());
    }

    function toArray(): array
    {
        $data = parent::toArray();

        $data[self::ATTR_ACTIONED_AT] = Carbon::create($data[self::ATTR_ACTIONED_AT], timezone: DateTimeZone::UTC)->timestamp;
        $data[self::ATTR_SYNCED_AT] = Carbon::create($data[self::ATTR_SYNCED_AT], timezone: DateTimeZone::UTC)->timestamp;

        return $data;
    }

    // ----------------------------------------------
    // GETTERS
    // ----------------------------------------------

    public function getAction(): string
    {
        return $this->getAttribute(self::ATTR_ACTION);
    }

    public function getEntity(): string
    {
        return $this->getAttribute(self::ATTR_ENTITY);
    }

    public function getData(): array
    {
        return json_decode($this->getAttribute(self::ATTR_DATA), true);
    }

    public function getActionedAt(): Carbon
    {
        return Carbon::create($this->getAttribute(self::ATTR_ACTIONED_AT), timezone: 'UTC');
    }

    public function getSyncedAt(): Carbon
    {
        return Carbon::create($this->getAttribute(self::ATTR_SYNCED_AT), timezone: 'UTC');
    }

    public function getUserId(): int|string
    {
        return $this->getAttribute(self::FK_USER_ID);
    }

    public function getOwnerId(): int|string
    {
        return $this->getAttribute(self::FK_OWNER_ID);
    }
}