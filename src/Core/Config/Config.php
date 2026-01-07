<?php

namespace AppTank\Horus\Core\Config;

use AppTank\Horus\Core\Config\Restriction\EntityRestriction;
use AppTank\Horus\Core\Entity\EntityReference;

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
     * @var EntityReference[]
     */
    private array $sharedEntities = [];

    /**
     * @var callable
     */
    private $onSetupSharedEntities;

    /**
     * @var callable|null The callback function to be called when validating if an entity was granted previously.
     */
    private $onValidateEntityWasGranted = null;

    /**
     * @var EntityRestriction[][]
     */
    private array $restrictionsByEntity = [];

    /**
     * @param bool $validateAccess Indicates whether access validation is enabled.
     * @param string|null $connectionName The name of the database connection, or null if not specified.
     * @param bool $usesUUIDs Indicates whether UUIDs are used as primary keys.
     * @param string|null $prefixTables The prefix for the tables, or null if not specified.
     * @param string $basePathFiles The base path for files, defaults to "horus".
     * @param EntityRestriction[] $entityRestrictions An array of entity restrictions.
     * @param EntityReference[] $sharedEntities An array of shared entities.
     * @param FeatureName[] $disabledFeatures An array of disabled features.
     * @param array $extraParametersReferenceFile An array of extra parameters for the reference file, when not use parameter as file reference.
     */
    function __construct(
        public readonly bool    $validateAccess = false,
        public readonly ?string $connectionName = null,
        public readonly bool    $usesUUIDs = false,
        public readonly ?string $prefixTables = "sync",
        string                  $basePathFiles = "horus",
        array                   $entityRestrictions = [],
        array                   $sharedEntities = [],
        public readonly array   $disabledFeatures = [],
        public readonly array   $extraParametersReferenceFile = []
    )
    {
        // Validate if ends with a slash
        if (str_ends_with($basePathFiles, '/')) {
            $this->basePathFiles = substr($basePathFiles, 0, -1);
        } else {
            $this->basePathFiles = $basePathFiles;
        }

        $this->entityRestrictions = $entityRestrictions;
        $this->sharedEntities = $sharedEntities;
        $this->populateRestrictionsByEntity();
    }

    /**
     * Sets the entity restrictions.
     *
     * @param EntityRestriction[] $entityRestrictions The entity restrictions.
     */
    function setEntityRestrictions(array $entityRestrictions): void
    {
        $this->entityRestrictions = $entityRestrictions;
        $this->populateRestrictionsByEntity();
    }

    /**
     * Sets the shared entities.
     *
     * @param EntityReference[] $sharedEntities The shared entities.
     */
    function setSharedEntities(array $sharedEntities): void
    {
        $this->sharedEntities = $sharedEntities;
    }


    /**
     * Sets the callback function to be called when setting up shared entities.
     *
     * @param callable $onSetupSharedEntities The callback function.
     */
    function setupOnSharedEntities(callable $onSetupSharedEntities): void
    {
        $this->onSetupSharedEntities = $onSetupSharedEntities;
    }

    /**
     * Sets the callback function to be called when validating if an entity was granted previously.
     *
     * @param callable $onValidateEntityWasGranted The callback function.
     */
    function setupOnValidateEntityWasGranted(callable $onValidateEntityWasGranted): void
    {
        $this->onValidateEntityWasGranted = $onValidateEntityWasGranted;
    }

    /**
     * Gets the path for the files that are pending to be uploaded.
     *
     * @return string The path for the pending files.
     */
    function getPathFilesUploads(): string
    {
        return $this->basePathFiles . "/upload";
    }

    /**
     * Gets the path for the files that are pending to be uploaded.
     *
     * @return string The path for the pending files.
     */
    function getPathFilesPending(): string
    {
        return $this->basePathFiles . "/upload-pending";
    }

    /**
     * Gets the path for the files that are being processed.
     *
     * @return string The path for the processing files.
     */
    function getPathFilesSync(): string
    {
        return $this->basePathFiles . "/sync";
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

    /**
     * Checks if there are restrictions for a specific entity.
     *
     * @param string $entityName The name of the entity.
     *
     * @return bool True if there are restrictions; otherwise, false.
     */
    function hasRestrictions(string $entityName): bool
    {
        return isset($this->restrictionsByEntity[$entityName]);
    }


    /**
     * Gets the restrictions for a specific entity.
     * @param string $entityName
     * @return EntityRestriction[]
     */
    function getRestrictionsByEntity(string $entityName): array
    {
        return $this->restrictionsByEntity[$entityName];
    }

    /**
     * Gets the callback function to be called when validating if an entity was granted previously.
     *
     * @return callable|null The callback function.
     */
    function getCallbackValidateEntityWasGranted(): callable|null
    {
        return $this->onValidateEntityWasGranted;
    }

    /**
     * Gets the shared entities.
     *
     * @return EntityReference[]
     */
    function getSharedEntities(): array
    {
        if (empty($this->sharedEntities) && $this->onSetupSharedEntities) {
            $this->sharedEntities = ($this->onSetupSharedEntities)();
        }
        return $this->sharedEntities;
    }

    private function populateRestrictionsByEntity(): void
    {
        foreach ($this->entityRestrictions as $restriction) {
            if (!isset($this->restrictionsByEntity[$restriction->getEntityName()])) {
                $this->restrictionsByEntity[$restriction->getEntityName()] = [];
            }
            $this->restrictionsByEntity[$restriction->getEntityName()][] = $restriction;
        }
    }

    /**
     * Serialize the Config object, excluding closures.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'validateAccess' => $this->validateAccess,
            'connectionName' => $this->connectionName,
            'usesUUIDs' => $this->usesUUIDs,
            'prefixTables' => $this->prefixTables,
            'basePathFiles' => $this->basePathFiles,
            'entityRestrictions' => $this->entityRestrictions,
            'sharedEntities' => $this->sharedEntities,
            'disabledFeatures' => $this->disabledFeatures,
            'extraParametersReferenceFile' => $this->extraParametersReferenceFile,
            'restrictionsByEntity' => $this->restrictionsByEntity,
            // Closures are not serialized
        ];
    }

    /**
     * Unserialize the Config object.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->validateAccess = $data['validateAccess'];
        $this->connectionName = $data['connectionName'];
        $this->usesUUIDs = $data['usesUUIDs'];
        $this->prefixTables = $data['prefixTables'];
        $this->basePathFiles = $data['basePathFiles'];
        $this->entityRestrictions = $data['entityRestrictions'];
        $this->sharedEntities = $data['sharedEntities'];
        $this->disabledFeatures = $data['disabledFeatures'];
        $this->extraParametersReferenceFile = $data['extraParametersReferenceFile'];
        $this->restrictionsByEntity = $data['restrictionsByEntity'];
        // Closures are initialized to null
        $this->onSetupSharedEntities = null;
        $this->onValidateEntityWasGranted = null;
    }
}
