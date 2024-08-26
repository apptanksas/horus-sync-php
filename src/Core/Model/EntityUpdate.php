<?php

namespace AppTank\Horus\Core\Model;

/**
 * @internal Class EntityUpdate
 *
 * Represents an update operation performed on an entity.
 * It extends the base EntityOperation class and includes additional data specific to an update operation.
 *
 * @package AppTank\Horus\Core\Model
 *
 * Author: John Ospina
 * Year: 2024
 */
class EntityUpdate extends EntityOperation
{
    /**
     * Constructor for the EntityUpdate class.
     *
     * @param string|int         $ownerId      The ID of the owner performing the operation.
     * @param string             $entity       The name of the entity being updated.
     * @param string             $id           The ID of the entity being updated.
     * @param \DateTimeImmutable $actionedAt   The date and time when the update was performed.
     * @param array              $attributes  The attributes that were updated in the entity.
     */
    function __construct(
        string|int            $ownerId,
        string                $entity,
        string                $id,
        \DateTimeImmutable    $actionedAt,
        public readonly array $attributes,
    )
    {
        parent::__construct($ownerId, $entity, $id, $actionedAt);
    }

    /**
     * Converts the entity update to an array representation.
     *
     * This method provides an array representation of the update operation, including the ID of the entity
     * and the attributes that were updated.
     *
     * @return array The array representation of the update operation.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'attributes' => $this->attributes
        ];
    }
}
