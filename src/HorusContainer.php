<?php

namespace AppTank\Horus;


use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Core\Mapper\EntityMapper;

class HorusContainer
{

    private static ?self $instance = null;

    private ?string $connectionName = null;

    private bool $usesUUIDs = false;

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
    public static function initialize(array $entitiesMap, ?string $connectionName = null, bool $usesUUIds = false): self
    {
        self::$instance = new self($entitiesMap);

        if (!is_null($connectionName)) {
            self::$instance->setConnectionName($connectionName);
        }
        self::$instance->usesUUIDs = $usesUUIds;

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

    public function setConnectionName(string $connectionName): void
    {
        $this->connectionName = $connectionName;
    }

    public function setUserAuthenticated(UserAuth $userAuth): void
    {
        $this->userAuth = $userAuth;
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
        return $this->connectionName;
    }

    public function isUsesUUID(): bool
    {
        return $this->usesUUIDs;
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