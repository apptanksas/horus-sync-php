<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Bus\IJobDispatcher;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\JobType;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Mock;
use Tests\Feature\Api\ApiTestCase;

class PostStartSyncDataJobApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private IJobDispatcher|Mock $jobDispatcher;
    private SyncJobRepository|Mock $syncJobRepository;

    private const array JSON_SCHEME = [
        'sync_id',
    ];

    function setUp(): void
    {
        parent::setUp();

        $this->jobDispatcher = $this->mock(IJobDispatcher::class);
        $this->syncJobRepository = $this->mock(SyncJobRepository::class);

        $this->app->bind(IJobDispatcher::class, fn() => $this->jobDispatcher);
        $this->app->bind(SyncJobRepository::class, fn() => $this->syncJobRepository);
    }

    function testStartSyncDataJobIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $syncId = $this->faker->uuid;
        $userAuth = new UserAuth($userId);

        Horus::getInstance()->setUserAuthenticated($userAuth)->setConfig(new Config(true));

        // Mocks
        $this->jobDispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (JobType $type, UserAuth $userAuth, SyncJob $syncJob) use ($syncId, $userId) {
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
        $response = $this->postJson(route(RouteName::POST_START_SYNC_DATA_JOB->value), [
            "sync_id" => $syncId
        ]);

        // Then
        $response->assertAccepted();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            'sync_id' => $syncId
        ]);
    }

    function testStartSyncDataJobWithIntegerUserIdIsSuccess()
    {
        // Given
        $userId = $this->faker->numberBetween(1, 1000);
        $syncId = $this->faker->uuid;
        $userAuth = new UserAuth($userId);

        Horus::getInstance()->setUserAuthenticated($userAuth)->setConfig(new Config(true));

        // Mocks
        $this->jobDispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (JobType $type, UserAuth $userAuth, SyncJob $syncJob) use ($syncId, $userId) {
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
        $response = $this->postJson(route(RouteName::POST_START_SYNC_DATA_JOB->value), [
            "sync_id" => $syncId
        ]);

        // Then
        $response->assertAccepted();
        $response->assertJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            'sync_id' => $syncId
        ]);
    }

    function testStartSyncDataJobFailsWithoutJobId()
    {
        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // When
        $response = $this->postJson(route(RouteName::POST_START_SYNC_DATA_JOB->value), []);

        // Then
        $response->assertBadRequest();
    }

    function testStartSyncDataJobFailsWithInvalidJobId()
    {
        // Given
        $userId = $this->faker->uuid;
        $invalidJobId = 'invalid-job-id';

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // When
        $response = $this->postJson(route(RouteName::POST_START_SYNC_DATA_JOB->value), [
            "sync_id" => $invalidJobId
        ]);

        // Then
        $response->assertBadRequest();
    }

    function testStartSyncDataJobFailsWithEmptyJobId()
    {
        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // When
        $response = $this->postJson(route(RouteName::POST_START_SYNC_DATA_JOB->value), [
            "sync_id" => ""
        ]);

        // Then
        $response->assertBadRequest();
    }

    function testStartSyncDataJobFailsWithNullJobId()
    {
        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        // When
        $response = $this->postJson(route(RouteName::POST_START_SYNC_DATA_JOB->value), [
            "sync_id" => null
        ]);

        // Then
        $response->assertBadRequest();
    }
} 