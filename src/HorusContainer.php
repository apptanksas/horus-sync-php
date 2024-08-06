<?php

namespace AppTank\Horus;


class HorusContainer
{

    private static ?self $instance = null;

    private ?string $connectionName = null;

    private bool $usesUUIDs = false;

    /**
     * @param string[] $entities Array of EntitySynchronizable class names
     */
    private function __construct(
        private readonly array $entities
    )
    {
    }

    /**
     * @param string[] $entities Array of EntitySynchronizable class names
     */
    public static function initialize(array $entities): self
    {
        self::$instance = new self($entities);
        return self::$instance;
    }

    // --------------------------------
    // SETT
    // --------------------------------

    public function setConnectionName(string $connectionName): void
    {
        $this->connectionName = $connectionName;
    }

    public function usesUUID(): void
    {
        $this->usesUUIDs = true;
    }

    // --------------------------------
    // GETTERS
    // --------------------------------


    public static function getInstance(): HorusContainer
    {
        return self::$instance ?? throw new \DomainException("HorusContainer not initialized");
    }

    /**
     * @return string[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    public function isUsesUUID(): bool
    {
        return $this->usesUUIDs;
    }


}