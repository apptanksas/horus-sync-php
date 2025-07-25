<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Mock;
use Tests\_Stubs\SyncJobFactory;
use Tests\Feature\Api\ApiTestCase;

class GetSyncDataJobStatusApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private SyncJobRepository|Mock $syncJobRepository;

    private const array JSON_SCHEMA = [
        'id',
        'user_id',
        'status',
        'result_at',
        'download_url',
        'checkpoint'
    ];

    function setUp(): void
    {
        parent::setUp();

        $this->syncJobRepository = $this->mock(SyncJobRepository::class);
        $this->app->bind(SyncJobRepository::class, fn() => $this->syncJobRepository);
    }

    /**
     * Test that the sync job status endpoint returns successful response with job details.
     */
    function testGetSyncDataJobStatusSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $downloadUrl = $this->faker->url;
        $checkpoint = $this->faker->dateTime()->getTimestamp();
        $resultAt = now()->toImmutable();
        
        $syncJob = SyncJobFactory::create(
            id: $syncId,
            userId: $userId,
            status: SyncJobStatus::COMPLETED,
            resultAt: $resultAt,
            downloadUrl: $downloadUrl,
            checkpoint: $checkpoint
        );

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([
            'id' => $syncId,
            'user_id' => $userId,
            'status' => strtolower(SyncJobStatus::COMPLETED->name),
            'result_at' => $resultAt->getTimestamp(),
            'download_url' => $downloadUrl,
            'checkpoint' => $checkpoint
        ]);
    }

    /**
     * Test that the sync job status endpoint returns pending status for a job in progress.
     */
    function testGetSyncDataJobStatusPendingSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        
        $syncJob = new \AppTank\Horus\Core\Model\SyncJob(
            id: $syncId,
            userId: $userId,
            status: SyncJobStatus::PENDING,
            resultAt: null,
            downloadUrl: null
        );

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([
            'id' => $syncId,
            'user_id' => $userId,
            'status' => strtolower(SyncJobStatus::PENDING->name),
            'result_at' => null,
            'download_url' => null
        ]);
    }

    /**
     * Test that the sync job status endpoint returns in_progress status.
     */
    function testGetSyncDataJobStatusInProgressSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        
        $syncJob = new \AppTank\Horus\Core\Model\SyncJob(
            id: $syncId,
            userId: $userId,
            status: SyncJobStatus::IN_PROGRESS,
            resultAt: null,
            downloadUrl: null
        );

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([
            'id' => $syncId,
            'user_id' => $userId,
            'status' => strtolower(SyncJobStatus::IN_PROGRESS->name)
        ]);
    }

    /**
     * Test that the sync job status endpoint returns failed status.
     */
    function testGetSyncDataJobStatusFailedSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $resultAt = now()->toImmutable();
        
        $syncJob = new \AppTank\Horus\Core\Model\SyncJob(
            id: $syncId,
            userId: $userId,
            status: SyncJobStatus::FAILED,
            resultAt: $resultAt,
            downloadUrl: null
        );

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([
            'id' => $syncId,
            'user_id' => $userId,
            'status' => strtolower(SyncJobStatus::FAILED->name),
            'result_at' => $resultAt->getTimestamp(),
            'download_url' => null
        ]);
    }

    /**
     * Test that the sync job status endpoint returns 404 when job is not found.
     */
    function testGetSyncDataJobStatusNotFound()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn(null);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertNotFound();
        $response->assertJson([
            'message' => "Sync job with ID $syncId not found"
        ]);
    }

    /**
     * Test that the sync job status endpoint returns 400 for invalid UUID.
     */
    function testGetSyncDataJobStatusInvalidId()
    {
        // Given
        $userId = $this->faker->uuid;
        $invalidSyncId = 'invalid-uuid';

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $invalidSyncId]));

        // Then
        $response->assertBadRequest();
        $response->assertJson([
            'message' => 'Invalid sync ID, must be a valid UUID'
        ]);
    }

    /**
     * Test that the sync job status endpoint with integer user ID works correctly.
     */
    function testGetSyncDataJobStatusWithIntegerUserIdSuccess()
    {
        // Given
        $userId = $this->faker->numberBetween(1, 1000);
        $syncId = $this->faker->uuid;
        
        $syncJob = SyncJobFactory::create(
            id: $syncId,
            userId: (string)$userId,
            status: SyncJobStatus::COMPLETED
        );

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEMA);
        $response->assertJson([
            'id' => $syncId,
            'user_id' => (string)$userId,
            'status' => strtolower(SyncJobStatus::COMPLETED->name)
        ]);
    }
} 