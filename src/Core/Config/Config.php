<?php

namespace AppTank\Horus\Core\Config;

/**
 * Class Config
 *
 * Represents the configuration settings for the application.
 *
 * @package AppTank\Horus\Core\Config
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class Config
{

    public string $basePathFiles;

    /**
     * @param bool $validateAccess Indicates whether access validation is enabled.
     * @param string|null $connectionName The name of the database connection, or null if not specified.
     * @param bool $usesUUIDs Indicates whether UUIDs are used as primary keys.
     */
    function __construct(
        public bool    $validateAccess = false,
        public ?string $connectionName = null,
        public bool    $usesUUIDs = false,
        public ?string $prefixTables = "sync",
        string         $basePathFiles = "horus/upload"
    )
    {
        // Validate if ends with a slash
        if (str_ends_with($basePathFiles, '/')) {
            $this->basePathFiles = substr($basePathFiles, 0, -1);
        } else {
            $this->basePathFiles = $basePathFiles;
        }
    }


    /**
     * Gets the path for the files that are pending to be uploaded.
     *
     * @return string The path for the pending files.
     */
    function getPathFilesPending(): string
    {
        return $this->basePathFiles . "/pending";
    }


}
