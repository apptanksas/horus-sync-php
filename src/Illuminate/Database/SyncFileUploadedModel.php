<?php

namespace AppTank\Horus\Illuminate\Database;

use AppTank\Horus\Horus;
use Illuminate\Database\Eloquent\Model;


class SyncFileUploadedModel extends Model
{
    const string TABLE_NAME = 'sync_files_uploaded';


    const string ATTR_ID = 'id';
    const string ATTR_MIME_TYPE = 'mime_type';
    const string ATTR_PATH = 'path';
    const string ATTR_PUBLIC_URL = 'public_url';
    const string ATTR_STATUS = 'status';
    const string FK_OWNER_ID = "owner_id";


    protected $table = self::TABLE_NAME;

    public $usesUniqueIds = true;

    protected $primaryKey = self::ATTR_ID;

    public $incrementing = false;

    protected $fillable = [
        self::ATTR_ID,
        self::ATTR_MIME_TYPE,
        self::ATTR_PATH,
        self::ATTR_PUBLIC_URL,
        self::ATTR_STATUS,
        self::FK_OWNER_ID,
        self::CREATED_AT
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


    function getId(): string
    {
        return $this->getAttribute(self::ATTR_ID);
    }

    function getMimeType(): string
    {
        return $this->getAttribute(self::ATTR_MIME_TYPE);
    }

    function getPath(): string
    {
        return $this->getAttribute(self::ATTR_PATH);
    }

    function getPublicUrl(): string
    {
        return $this->getAttribute(self::ATTR_PUBLIC_URL);
    }

    function getOwnerId(): string
    {
        return $this->getAttribute(self::FK_OWNER_ID);
    }

    function getStatus(): string
    {
        return $this->getAttribute(self::ATTR_STATUS);
    }
}