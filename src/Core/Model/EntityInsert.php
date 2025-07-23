<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Hasher;

/**
 * @internal Class EntityInsert
 *
 * Represents an insert operation for an entity. This class is used to handle the insertion
 * of a new entity, including validation of the provided data and computing a hash for it.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
class EntityInsert extends EntityOperation
{
    private ?string $hash = null;

    /**
     * Constructor for the EntityInsert class.
     *
     * @param string|int         $ownerId The ID of the owner performing the operation.
     * @param string             $entity  The name of the entity being inserted.
     * @param \DateTimeImmutable $actionedAt The date and time when the action was performed.
     * @param array              $data   The data associated with the entity insertion. Must include an 'id'.
     *
     * @throws ClientException If the provided data is invalid (empty, missing 'id', or not enough attributes).
     */
    public function __construct(
        string|int         $ownerId,
        string             $entity,
        \DateTimeImmutable $actionedAt,
        public array       $data,
    )
    {
        $this->validateData();
        $this->hash = Hasher::hash($data);
        parent::__construct($ownerId, $entity, $this->data["id"], $actionedAt);
    }


    public function cloneWithOwnerId(int|string $ownerId): self
    {
        return new self(
            $ownerId,
            $this->entity,
            $this->actionedAt,
            $this->data
        );
    }

    /**
     * Validates the provided data for the entity insertion.
     *
     * @throws ClientException If the data is empty, missing an 'id', or has less than two attributes.
     */
    private function validateData(): void
    {
        if (empty($this->data)) {
            throw new ClientException('Data cannot be empty');
        }

        if (!isset($this->data['id'])) {
            throw new ClientException('Data must have an id');
        }

        if (count($this->data) < 2) {
            throw new ClientException('Data must have at least one attribute');
        }
    }

    /**
     * Returns the data associated with the entity insertion.
     *
     * @return array The data for the entity insertion.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Returns the hash of the entity data.
     *
     * @return string The hash computed for the entity data.
     */
    public function hash(): string
    {
        return $this->hash;
    }
}
