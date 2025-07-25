<?php

namespace Tests\Unit\Application\Sync;

use AppTank\Horus\Application\Sync\SearchSyncDataStatus;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use Mockery\Mock;
use Tests\_Stubs\SyncJobFactory;
use Tests\TestCase;

class SearchSyncDataStatusTest extends TestCase
{
    private SyncJobRepository|Mock $syncJobRepository;
    private SearchSyncDataStatus $searchSyncDataStatus;

    public function setUp(): void
    {
        parent::setUp();

        $this->syncJobRepository = $this->mock(SyncJobRepository::class);

        $this->searchSyncDataStatus = new SearchSyncDataStatus(
            $this->syncJobRepository
        );
    }

    public function testInvokeIsSuccess()
    {
        // Given
        $syncId = $this->faker->uuid;
        $syncJob = SyncJobFactory::create($syncId);

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $result = $this->searchSyncDataStatus->__invoke($syncId);

        // Then
        $expectedArray = [
            'id' => $syncJob->id,
            'user_id' => $syncJob->userId,
            'status' => strtolower($syncJob->status->name),
            'result_at' => $syncJob->resultAt?->getTimestamp(),
            'download_url' => $syncJob->downloadUrl,
        ];

        $this->assertEquals($expectedArray, $result);
    }

    public function testInvokeWithDifferentUserIdIsSuccess()
    {
        // Given
        $syncId = $this->faker->uuid;
        $userId = $this->faker->numberBetween(1, 1000); // Test with integer userId
        $syncJob = SyncJobFactory::create($syncId, $userId);

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $result = $this->searchSyncDataStatus->__invoke($syncId);

        // Then
        $expectedArray = [
            'id' => $syncJob->id,
            'user_id' => $syncJob->userId,
            'status' => strtolower($syncJob->status->name),
            'result_at' => $syncJob->resultAt?->getTimestamp(),
            'download_url' => $syncJob->downloadUrl,
        ];

        $this->assertEquals($expectedArray, $result);
    }

    public function testInvokeThrowsClientExceptionWhenJobNotFound()
    {
        // Given
        $syncId = $this->faker->uuid;

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn(null);

        // When & Then
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Sync job with ID $syncId not found");

        $this->searchSyncDataStatus->__invoke($syncId);
    }

    public function testInvokeHandlesDifferentSyncIds()
    {
        // Given
        $syncId1 = $this->faker->uuid;
        $syncId2 = $this->faker->uuid;
        $syncJob1 = SyncJobFactory::create($syncId1);
        $syncJob2 = SyncJobFactory::create($syncId2);

        // Mock for first call
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId1)
            ->andReturn($syncJob1);

        // Mock for second call
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId2)
            ->andReturn($syncJob2);

        // When
        $result1 = $this->searchSyncDataStatus->__invoke($syncId1);
        $result2 = $this->searchSyncDataStatus->__invoke($syncId2);

        // Then
        $this->assertEquals($syncJob1->id, $result1['id']);
        $this->assertEquals($syncJob2->id, $result2['id']);
        $this->assertNotEquals($result1['id'], $result2['id']);
    }
} 