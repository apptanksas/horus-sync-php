<?php

namespace AppTank\Horus;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\EntityRestriction;
use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Mapper\EntityMapper;

/**
 * Horus is a singleton class responsible for managing the application's configuration,
 * entity mappings, and user authentication. It provides methods for initializing the container,
 * setting and getting configuration values, and managing entities and middlewares.
 */
class Horus
{

    private static ?self $instance = null;

    private Config $config;

    private IFileHandler $fileHandler;

    private UserAuth|null $userAuth = null;

    private EntityMapper $entityMapper;

    private array $middlewares = [];

    /**
     * Private constructor to initialize the container with entity mappings.
     *
     * @param array $entitiesMap Array of EntitySynchronizable class names.
     */
    private function __construct(array $entitiesMap)
    {
        $this->entityMapper = new EntityMapper();
        $this->populateEntitiesMapper($entitiesMap);
        foreach ($this->createMap($entitiesMap) as $entityMap) {
            $this->entityMapper->pushMap($entityMap);
        }
    }

    /**
     * Initializes the singleton instance of the container with entity mappings.
     *
     * @param array $entitiesMap Array of EntitySynchronizable class names.
     * @return self The singleton instance of the container.
     */
    public static function initialize(array $entitiesMap): self
    {
        self::$instance = new self($entitiesMap);
        self::$instance->config = new Config();
        return self::$instance;
    }

    // --------------------------------
    // OPERATIONS
    // --------------------------------

    /**
     * Populates the entity mapper with the provided entities.
     *
     * @param array $entitiesMap Array of EntitySynchronizable class names.
     * @return void
     */
    private function populateEntitiesMapper(array $entitiesMap): void
    {
        foreach ($entitiesMap as $entityIndex => $entities) {
            if (is_array($entities)) {
                if (class_exists($entityIndex)) {
                    $this->entityMapper->pushEntity($entityIndex);
                }
                $this->populateEntitiesMapper($entities);
                continue;
            }
            $this->entityMapper->pushEntity($entities);
        }
    }

    /**
     * Creates a map of entities from the provided array.
     *
     * @param array $entitiesMap Array of EntitySynchronizable class names.
     * @return EntityMap[] Array of EntityMap instances.
     */
    private function createMap(array $entitiesMap): array
    {
        $output = [];
        foreach ($entitiesMap as $entityIndex => $entities) {
            if (is_array($entities) && class_exists($entityIndex)) {
                $output[] = new EntityMap($entityIndex::getEntityName(), $this->createMap($entities));
                continue;
            }
            $output[] = new EntityMap($entities::getEntityName());
        }
        return $output;
    }

    // --------------------------------
    // SETTERS
    // --------------------------------

    /**
     * Sets the configuration for the container.
     *
     * @param Config $config The configuration object.
     * @return self The container instance.
     */
    public function setConfig(Config $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Sets the user authentication object.
     *
     * @param UserAuth $userAuth The user authentication object.
     * @return self The container instance.
     */
    public function setUserAuthenticated(UserAuth $userAuth): self
    {
        $this->userAuth = $userAuth;
        return $this;
    }

    /**
     * Sets the restrictions for the container.
     *
     * @param EntityRestriction[] $restrictions Array of restrictions.
     * @return void
     */
    public function setEntityRestrictions(array $restrictions): void
    {
        $this->config->setEntityRestrictions($restrictions);
    }

    /**
     * Sets the middlewares for the container.
     *
     * @param array $middlewares Array of middlewares.
     * @return void
     */
    public static function setMiddlewares(array $middlewares): void
    {
        self::getInstance()->middlewares = $middlewares;
    }

    /**
     * Sets the file handler for the container.
     *
     * @param IFileHandler $fileHandler The file handler instance.
     * @return void
     */
    public static function setFileHandler(IFileHandler $fileHandler)
    {
        self::getInstance()->fileHandler = $fileHandler;
    }

    // --------------------------------
    // GETTERS
    // --------------------------------

    /**
     * Gets the singleton instance of the container.
     *
     * @return self The singleton instance of the container.
     */
    public static function getInstance(): Horus
    {
        if (is_null(self::$instance)) {
            self::initialize([]);
        }

        return self::$instance;
    }

    /**
     * Gets the configuration object.
     *
     * @return Config The configuration object.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Gets the list of entities managed by the entity mapper.
     *
     * @return string[] Array of entity names.
     */
    public function getEntities(): array
    {
        return $this->entityMapper->getEntities();
    }

    /**
     * Gets the entity mapper.
     *
     * @return EntityMapper The entity mapper instance.
     */
    public function getEntityMapper(): EntityMapper
    {
        return $this->entityMapper;
    }

    /**
     * Gets the connection name from the configuration.
     *
     * @return string|null The connection name or null if not set.
     */
    public function getConnectionName(): ?string
    {
        return $this->config->connectionName;
    }

    /**
     * Checks if UUIDs are used based on the configuration.
     *
     * @return bool True if UUIDs are used, false otherwise.
     */
    public function isUsesUUID(): bool
    {
        return $this->config->usesUUIDs;
    }

    /**
     * Checks if access validation is enabled based on the configuration.
     *
     * @return bool True if access validation is enabled, false otherwise.
     */
    public function isValidateAccess(): bool
    {
        return $this->config->validateAccess;
    }

    /**
     * Gets the user authentication object.
     *
     * @return UserAuth|null The user authentication object or null if not set.
     */
    public function getUserAuthenticated(): ?UserAuth
    {
        return $this->userAuth;
    }

    /**
     * Gets the middlewares.
     *
     * @return array Array of middlewares.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Gets the file handler.
     *
     * @return IFileHandler The file handler instance.
     */
    public function getFileHandler(): IFileHandler
    {
        return $this->fileHandler;
    }
}
