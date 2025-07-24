<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


/**
 * @internal Class SyncJobModel
 *
 * This class is used to manage synchronization jobs, including their status, download URLs,
 * and timestamps for when they were processed. It is part of the Horus application framework.
 *
 * @package AppTank\Horus\Illuminate\Database
 */
final class SyncJobModel extends Model
{
    // Table and column names
    const string TABLE_NAME = 'sync_jobs';

    const string ATTR_ID = 'id'; // Primary key of the job
    const string ATTR_STATUS = 'status'; // Status of the job (e.g., pending, completed, failed)
    const string ATTR_DOWNLOAD_URL = 'download_url'; // URL to download the file associated with the job

    const string ATTR_RESULTED_AT = 'resulted_at'; // Timestamp when the job was processed

    const string FK_USER_ID = "user_id"; // User Identifier

    protected $table = self::TABLE_NAME;
    public $timestamps = true;

    protected $fillable = [
        self::ATTR_ID,
        self::ATTR_STATUS,
        self::ATTR_DOWNLOAD_URL,
        self::ATTR_RESULTED_AT,
        self::FK_USER_ID
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

        $data[self::ATTR_RESULTED_AT] = Carbon::parse($data[self::ATTR_RESULTED_AT], "UTC")->timestamp;

        return $data;
    }

    // ----------------------------------------------
    // GETTERS
    // ----------------------------------------------

    /**
     * Get the ID of the job.
     *
     * @return int|string
     */
    public function getId(): int|string
    {
        return $this->getAttribute(self::ATTR_ID);
    }

    /**
     * Get the Status job.
     *
     * @return SyncJobStatus
     */
    public function getStatus(): SyncJobStatus
    {
        $status = $this->getAttribute(self::ATTR_STATUS);

        if (is_null($status)) {
            return SyncJobStatus::PENDING;
        }

        return SyncJobStatus::from($status);
    }


    /**
     * Get the download URL for the job.
     *
     * @return string|null The URL to download the file associated with the job, or null if not set.
     */
    public function getDownloadUrl(): string|null
    {
        return $this->getAttribute(self::ATTR_DOWNLOAD_URL);
    }


    /**
     * Get the timestamp when the job was processed.
     *
     * @return \DateTimeImmutable|null The timestamp when the job was processed, or null if not set.
     */
    public function getResultAt(): \DateTimeImmutable|null
    {
        return \DateTimeImmutable::createFromMutable((new DateTimeUtil())->parseDatetime($this->getAttribute(self::ATTR_RESULTED_AT)));
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

}
