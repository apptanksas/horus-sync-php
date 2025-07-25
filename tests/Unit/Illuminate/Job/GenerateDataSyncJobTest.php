<?php

namespace Tests\Unit\Illuminate\Job;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\IGetDataEntitiesUseCase;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Illuminate\Job\GenerateDataSyncJob;
use Mockery\Mock;
use Tests\TestCase;

class GenerateDataSyncJobTest extends TestCase
{
    private IGetDataEntitiesUseCase|Mock $getDataEntitiesUseCase;
    private SyncJobRepository|Mock $syncJobRepository;
    private IFileHandler|Mock $fileHandler;
    private Config|Mock $config;
    private GenerateDataSyncJob $generateDataSyncJob;

    public function setUp(): void
    {
        parent::setUp();

        $this->getDataEntitiesUseCase = $this->mock(IGetDataEntitiesUseCase::class);
        $this->syncJobRepository = $this->mock(SyncJobRepository::class);
        $this->fileHandler = $this->mock(IFileHandler::class);
        $this->config = $this->mock(Config::class);
    }

    public function testGenerateDataIsSuccess()
    {
        // Given
        $userAuth = new UserAuth($this->faker->uuid);
        $syncJob = new SyncJob($this->faker->uuid, $userAuth->getEffectiveUserId(), SyncJobStatus::PENDING);
        $dataResult = $this->generateArray(fn() => $this->faker->randomElements());
        $fileUrlExpected = $this->faker->url;

        $this->getDataEntitiesUseCase->shouldReceive("__invoke")->andReturn($dataResult);
        $this->config->shouldReceive("getPathFilesSync")->andReturn($this->faker->filePath());

        $this->fileHandler->shouldReceive("createDownloadableTemporaryFile")->once()->withArgs(function ($pathFile, $data, $contentType) use ($dataResult) {
            return $data == json_encode($dataResult);
        })->andReturn($fileUrlExpected);

        // Validate save in progress
        $this->syncJobRepository->shouldReceive('save')
            ->once()
            ->withArgs(function ($syncJob) use ($userAuth) {
                return $syncJob->userId === $userAuth->getEffectiveUserId() &&
                    $syncJob->status === SyncJobStatus::IN_PROGRESS &&
                    $syncJob->resultAt === null && $syncJob->downloadUrl === null;
            });

        // Validate save completed
        $this->syncJobRepository->shouldReceive('save')
            ->once()
            ->withArgs(function ($syncJob) use ($userAuth) {
                return $syncJob->userId === $userAuth->getEffectiveUserId() &&
                    $syncJob->status === SyncJobStatus::SUCCESS &&
                    $syncJob->resultAt != null && filter_var($syncJob->downloadUrl, FILTER_VALIDATE_URL);
            });

        // When
        $this->generateDataSyncJob = new GenerateDataSyncJob(
            $userAuth,
            $syncJob,
        );
        $this->generateDataSyncJob->handle($this->getDataEntitiesUseCase,
            $this->syncJobRepository,
            $this->fileHandler,
            $this->config
        );
    }

    public function testGenerateDataWithException()
    {
        // Given
        $userAuth = new UserAuth($this->faker->uuid);
        $syncJob = new SyncJob($this->faker->uuid, $userAuth->getEffectiveUserId(), SyncJobStatus::PENDING);

        $this->getDataEntitiesUseCase->shouldReceive("__invoke")->andThrow(new \Exception("Error generating data"));

        // Validate save in progress
        $this->syncJobRepository->shouldReceive('save')
            ->once()
            ->withArgs(function ($syncJob) use ($userAuth) {
                return $syncJob->userId === $userAuth->getEffectiveUserId() &&
                    $syncJob->status === SyncJobStatus::IN_PROGRESS &&
                    $syncJob->resultAt === null && $syncJob->downloadUrl === null;
            });

        // Validate save failed
        $this->syncJobRepository->shouldReceive('save')
            ->once()
            ->withArgs(function ($syncJob) use ($userAuth) {
                return $syncJob->userId === $userAuth->getEffectiveUserId() &&
                    $syncJob->status === SyncJobStatus::FAILED &&
                    $syncJob->resultAt == null && is_null($syncJob->downloadUrl);
            });

        // When
        $this->generateDataSyncJob = new GenerateDataSyncJob(
            $userAuth,
            $syncJob,
        );
        $this->generateDataSyncJob->handle($this->getDataEntitiesUseCase,
            $this->syncJobRepository,
            $this->fileHandler,
            $this->config
        );
    }

} 