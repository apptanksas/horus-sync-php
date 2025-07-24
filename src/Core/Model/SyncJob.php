<?php

namespace AppTank\Horus\Core\Model;

use AppTank\Horus\Core\SyncJobStatus;

/**
 * @internal Class SyncJob
 *
 * Represents a synchronization job with its associated properties.
 *
 * @package AppTank\Horus\Core\Model
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class SyncJob
{
    /**
     * @param string $id
     * @param string|int $userId
     * @param SyncJobStatus $status
     * @param \DateTimeImmutable|null $resultAt
     * @param string|null $downloadUrl
     */
    public function __construct(
        public string              $id,
        public string|int          $userId,
        public SyncJobStatus       $status = SyncJobStatus::PENDING,
        public ?\DateTimeImmutable $resultAt = null,
        public ?string             $downloadUrl = null,
    )
    {

    }
}