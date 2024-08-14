<?php

namespace AppTank\Horus\Core\Entity;


interface IEntitySynchronizable
{
    /**
     * @return SyncParameter[]
     */
    public static function parameters(): array;

    /**
     *  Get the entity name
     * @return string
     */
    public static function getEntityName(): string;

    /**
     * Get the version number
     * @return int
     */
    public static function getVersionNumber(): int;

    public static function baseParameters(): array;

    public static function getTableName(): string;

    public static function schema(): array;
}