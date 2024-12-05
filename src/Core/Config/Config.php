<?php

namespace AppTank\Horus\Core\Config;

use AppTank\Horus\Core\Config\Restriction\EntityRestriction;

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
class Config
{

    public readonly string $basePathFiles;

    /** @var EntityRestriction[] */
    private array $entityRestrictions = [];

    /**
     * @param bool $validateAccess Indicates whether access validation is enabled.
     * @param string|null $connectionName The name of the database connection, or null if not specified.
     * @param bool $usesUUIDs Indicates whether UUIDs are used as primary keys.
     * @param EntityRestriction[] $entityRestrictions An array of entity restrictions.
     */
    function __construct(
        public readonly bool    $validateAccess = false,
        public readonly ?string $connectionName = null,
        public readonly bool    $usesUUIDs = false,
        public readonly ?string $prefixTables = "sync",
        string                  $basePathFiles = "horus/upload",
        array                   $entityRestrictions = []
    )
    {
        // Validate if ends with a slash
        if (str_ends_with($basePathFiles, '/')) {
            $this->basePathFiles = substr($basePathFiles, 0, -1);
        } else {
            $this->basePathFiles = $basePathFiles;
        }

        $this->entityRestrictions = $entityRestrictions;
    }

    /**
     * Sets the entity restrictions.
     *
     * @param EntityRestriction[] $entityRestrictions The entity restrictions.
     */
    function setEntityRestrictions(array $entityRestrictions): void
    {
        $this->entityRestrictions = $entityRestrictions;
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

    /**
     * Gets the entity restrictions.
     *
     * @return EntityRestriction[]
     */
    function getEntityRestrictions(): array
    {
        return $this->entityRestrictions;
    }


}
