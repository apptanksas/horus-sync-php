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
     * @param int|null $checkpoint
     */
    public function __construct(
        public string              $id,
        public string|int          $userId,
        public SyncJobStatus       $status = SyncJobStatus::PENDING,
        public ?\DateTimeImmutable $resultAt = null,
        public ?string             $downloadUrl = null,
        public int|null            $checkpoint = null,
    )
    {

    }


    /**
     * Converts the SyncJob instance to an array representation.
     *
     * @return array The array representation of the SyncJob.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'status' => strtolower($this->status->name),
            'result_at' => $this->resultAt?->getTimestamp(),
            'download_url' => $this->downloadUrl,
            'checkpoint' => $this->checkpoint,
        ];
    }
}