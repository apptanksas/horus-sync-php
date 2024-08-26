<?php

namespace AppTank\Horus;


use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Core\Mapper\EntityMapper;

class HorusContainer
{

    private static ?self $instance = null;

    private Config $config;

    private UserAuth|null $userAuth = null;

    private EntityMapper $entityMapper;

    private array $middlewares = [];

    /**
     * @param array $entitiesMap Array of EntitySynchronizable class names
     */
    private function __construct(
        array $entitiesMap
    )
    {
        $this->entityMapper = new EntityMapper();

        $this->populateEntitiesMapper($entitiesMap);
        foreach ($this->createMap($entitiesMap) as $entityMap) {
            $this->entityMapper->pushMap($entityMap);
        }
    }

    /**
     * @param array $entitiesMap Array of EntitySynchronizable class names
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
     * Populate the entity mapper with the entities
     *
     * @param array $entitiesMap
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
     * @param array $entitiesMap
     * @return EntityMap[]
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


    public function setConfig(Config $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function setUserAuthenticated(UserAuth $userAuth): self
    {
        $this->userAuth = $userAuth;
        return $this;
    }

    public static function setMiddlewares(array $middlewares): void
    {
        self::getInstance()->middlewares = $middlewares;
    }

    // --------------------------------
    // GETTERS
    // --------------------------------


    public static function getInstance(): HorusContainer
    {
        if (is_null(self::$instance)) {
            self::initialize([]);
        }

        return self::$instance;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return string[]
     */
    public function getEntities(): array
    {
        return $this->entityMapper->getEntities();
    }

    public function getEntityMapper(): EntityMapper
    {
        return $this->entityMapper;
    }

    public function getConnectionName(): ?string
    {
        return $this->config->connectionName;
    }

    public function isUsesUUID(): bool
    {
        return $this->config->usesUUIDs;
    }

    public function isValidateAccess(): bool
    {
        return $this->config->validateAccess;
    }

    public function getUserAuthenticated(): ?UserAuth
    {
        return $this->userAuth;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}