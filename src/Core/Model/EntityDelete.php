<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class EntityDelete
 *
 * Represents an entity deletion operation. It extends from the EntityOperation class and includes
 * a method to convert the operation details to an array.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
class EntityDelete extends EntityOperation
{
    /**
     * Constructor for the EntityDelete class.
     *
     * @param string|int $ownerId The ID of the owner of the entity.
     * @param string $entity The name of the entity to be deleted.
     * @param string $id The ID of the entity to be deleted.
     * @param \DateTimeImmutable $actionedAt The timestamp when the deletion action was performed.
     */
    function __construct(
        string|int         $ownerId,
        string             $entity,
        string             $id,
        \DateTimeImmutable $actionedAt,
    )
    {
        parent::__construct($ownerId, $entity, $id, $actionedAt);
    }

    /**
     * Converts the entity delete operation details to an associative array.
     *
     * @return array An associative array with the 'id' of the entity to be deleted.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id
        ];
    }
}
