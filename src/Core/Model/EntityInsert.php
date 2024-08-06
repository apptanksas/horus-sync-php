<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\Hasher;

class EntityInsert extends EntityOperation
{
    private ?string $hash = null;

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

    private function validateData(): void
    {
        if (empty($this->data)) {
            throw new \InvalidArgumentException('Data cannot be empty');
        }

        if (!isset($this->data['id'])) {
            throw new \InvalidArgumentException('Data must have an id');
        }

        if (count($this->data) < 2) {
            throw new \InvalidArgumentException('Data must have at least one attribute');
        }
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function hash(): string
    {
        return $this->hash;
    }
}