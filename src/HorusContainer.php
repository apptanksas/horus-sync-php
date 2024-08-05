<?php

namespace AppTank\Horus;

use AppTank\Horus\Core\Entity\EntitySynchronizable;

class HorusContainer
{

    private static ?self $instance = null;

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

}