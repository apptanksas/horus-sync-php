<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class EntityOperation
 *
 * An abstract class representing a generic operation performed on an entity.
 * It serves as a base class for specific entity operations such as inserts, updates, and deletes.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
abstract class EntityOperation
{
    /**
     * Constructor for the EntityOperation class.
     *
     * @param string|int $ownerId The ID of the owner performing the operation.
     * @param string $entity The name of the entity involved in the operation.
     * @param string $id The ID of the entity involved in the operation.
     * @param \DateTimeImmutable $actionedAt The date and time when the action was performed.
     */
    public function __construct(
        public readonly string|int         $ownerId,
        public readonly string             $entity,
        public readonly string             $id,
        public readonly \DateTimeImmutable $actionedAt,
    )
    {

    }

    /**
     * Clones the entity operation with a new owner ID.
     *
     * This method must be implemented by subclasses to provide a specific cloning behavior
     * for the operation with a new owner ID.
     *
     * @param string|int $ownerId The new owner ID for the cloned operation.
     * @return static A new instance of the entity operation with the specified owner ID.
     */
    public abstract function cloneWithOwnerId(string|int $ownerId): self;

    /**
     * Converts the entity operation to an array representation.
     *
     * This method must be implemented by subclasses to provide a specific array representation
     * of the operation data.
     *
     * @return array The array representation of the operation.
     */
    public abstract function toArray(): array;


}
