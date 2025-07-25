<?php

namespace Tests\Unit\Application\Sync;

use AppTank\Horus\Application\Sync\StartGenerateSyncDataJob;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IJobDispatcher;
use AppTank\Horus\Core\JobType;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\SyncJobStatus;
use Mockery\Mock;
use Tests\TestCase;

class StartGenerateSyncDataJobTest extends TestCase
{
    private SyncJobRepository|Mock $syncJobRepository;
    private IJobDispatcher|Mock $jobDispatcher;
    private StartGenerateSyncDataJob $startGenerateSyncDataJob;

    public function setUp(): void
    {
        parent::setUp();

        $this->syncJobRepository = $this->mock(SyncJobRepository::class);
        $this->jobDispatcher = $this->mock(IJobDispatcher::class);

        $this->startGenerateSyncDataJob = new StartGenerateSyncDataJob(
            $this->syncJobRepository,
            $this->jobDispatcher
        );
    }

    public function testInvokeIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $userAuth = new UserAuth($userId);

        // Mocks
        $this->jobDispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (JobType $type, SyncJob $syncJob) use ($syncId, $userId) {
                return $type === JobType::GENERATE_SYNC_DATA &&
                    $syncJob->id === $syncId &&
                    $syncJob->userId === $userId &&
                    $syncJob->status === SyncJobStatus::PENDING &&
                    $syncJob->resultAt === null &&
                    $syncJob->downloadUrl === null;
            });

        $this->syncJobRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (SyncJob $syncJob) use ($syncId, $userId) {
                return $syncJob->id === $syncId &&
                    $syncJob->userId === $userId &&
                    $syncJob->status === SyncJobStatus::PENDING &&
                    $syncJob->resultAt === null &&
                    $syncJob->downloadUrl === null;
            });

        // When
        $this->startGenerateSyncDataJob->__invoke($userAuth, $syncId);
    }

    public function testInvokeWithDifferentUserIds()
    {
        // Given
        $userId = $this->faker->numberBetween(1, 1000); // Test with integer userId
        $syncId = $this->faker->uuid;
        $userAuth = new UserAuth($userId);

        // Mocks
        $this->jobDispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (JobType $type, SyncJob $syncJob) use ($syncId, $userId) {
                return $type === JobType::GENERATE_SYNC_DATA &&
                    $syncJob->id === $syncId &&
                    $syncJob->userId === $userId &&
                    $syncJob->status === SyncJobStatus::PENDING;
            });

        $this->syncJobRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (SyncJob $syncJob) use ($syncId, $userId) {
                return $syncJob->id === $syncId &&
                    $syncJob->userId === $userId &&
                    $syncJob->status === SyncJobStatus::PENDING;
            });

        // When
        $this->startGenerateSyncDataJob->__invoke($userAuth, $syncId);

        // Then - Assertions are handled by the mock expectations
    }
} 