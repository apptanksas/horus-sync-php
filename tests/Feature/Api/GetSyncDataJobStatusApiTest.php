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

    private const array JSON_SCHEME = [
        'id',
        'user_id',
        'status',
        'result_at',
        'download_url'
    ];

    function setUp(): void
    {
        parent::setUp();

        $this->syncJobRepository = $this->mock(SyncJobRepository::class);
        $this->app->bind(SyncJobRepository::class, fn() => $this->syncJobRepository);
    }

    function testGetSyncDataJobStatusIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $syncJob = SyncJobFactory::create($syncId, $userId);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            'id' => $syncJob->id,
            'user_id' => $syncJob->userId,
            'status' => strtolower($syncJob->status->name),
            'result_at' => $syncJob->resultAt?->getTimestamp(),
            'download_url' => $syncJob->downloadUrl,
        ]);
    }

    function testGetSyncDataJobStatusWithIntegerUserIdIsSuccess()
    {
        // Given
        $userId = $this->faker->numberBetween(1, 1000);
        $syncId = $this->faker->uuid;
        $syncJob = SyncJobFactory::create($syncId, $userId);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            'id' => $syncJob->id,
            'user_id' => $syncJob->userId,
            'status' => strtolower($syncJob->status->name),
            'result_at' => $syncJob->resultAt?->getTimestamp(),
            'download_url' => $syncJob->downloadUrl,
        ]);
    }

    function testGetSyncDataJobStatusWithDifferentStatusesIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $syncJob = SyncJobFactory::create($syncId, $userId, SyncJobStatus::COMPLETED);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            'id' => $syncJob->id,
            'status' => 'completed', // Should be lowercase
        ]);
    }

    function testGetSyncDataJobStatusNotFound()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn(null);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertNotFound();
    }

    function testGetSyncDataJobStatusFailsWithInvalidUUID()
    {
        // Given
        $userId = $this->faker->uuid;
        $invalidSyncId = 'invalid-uuid';

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $invalidSyncId]));

        // Then
        $response->assertBadRequest();
    }

    function testGetSyncDataJobStatusWithResultAtAndDownloadUrl()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $resultAt = new \DateTimeImmutable();
        $downloadUrl = $this->faker->url;
        
        $syncJob = SyncJobFactory::create(
            $syncId, 
            $userId, 
            SyncJobStatus::COMPLETED, 
            $resultAt, 
            $downloadUrl
        );

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // Mock
        $this->syncJobRepository->shouldReceive('search')
            ->once()
            ->with($syncId)
            ->andReturn($syncJob);

        // When
        $response = $this->get(route(RouteName::GET_SYNC_DATA_JOB_STATUS->value, ['id' => $syncId]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            'id' => $syncJob->id,
            'user_id' => $syncJob->userId,
            'status' => 'completed',
            'result_at' => $resultAt->getTimestamp(),
            'download_url' => $downloadUrl,
        ]);
    }
} 