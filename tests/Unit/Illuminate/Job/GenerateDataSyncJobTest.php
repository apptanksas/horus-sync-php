<?php

namespace Tests\Unit\Illuminate\Job;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Model\SyncJob;
use AppTank\Horus\Core\Repository\IGetDataEntitiesUseCase;
use AppTank\Horus\Core\Repository\SyncJobRepository;
use AppTank\Horus\Core\SyncJobStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Job\GenerateDataSyncJob;
use AppTank\Horus\Illuminate\Util\EntitiesDataParser;
use Mockery\Mock;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\TestCase;
use function Orchestra\Testbench\package_path;

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
        $countEntitiesExpected = 0;

        $dataResult = EntitiesDataParser::parseToArrayRaw($this->generateCountArray(function () use (&$countEntitiesExpected) {
            $parent = ParentFakeEntityFactory::newData();
            $countEntitiesExpected++;
            $parent["_children"] = $this->generateCountArray(function () use ($parent, &$countEntitiesExpected) {
                $countEntitiesExpected++;
                return (new EntityData(ChildFakeWritableEntity::getEntityName(), ChildFakeEntityFactory::newData($parent["id"])));
            });
            $parent["_empty"] = [];

            return new EntityData(ParentFakeWritableEntity::getEntityName(), $parent);
        }, 5));

        $fileUrlExpected = $this->faker->url;

        $this->getDataEntitiesUseCase->shouldReceive("__invoke")->andReturn($dataResult);
        $this->config->shouldReceive("getPathFilesSync")->andReturn($this->faker->filePath());

        // Validate file creation
        $this->fileHandler->shouldReceive("createDownloadableTemporaryFile")->once()->withArgs(function ($pathFile, $data, $contentType) use ($countEntitiesExpected) {

            $entities = explode(PHP_EOL, $data);
            $this->assertCount($countEntitiesExpected, $entities);
            $parentIndex = -1;
            $childIndex = -1;

            foreach ($entities as $index => $entity) {
                $this->assertJson($entity);

                if (json_decode($entity, true)["entity"] == ParentFakeWritableEntity::getEntityName()) {
                    $parentIndex = $index;
                }

                if (json_decode($entity, true)["entity"] == ChildFakeWritableEntity::getEntityName()) {
                    $childIndex = $index;
                }

                // Validate parent appears before child
                if ($parentIndex != -1 && $childIndex != -1) {
                    $this->assertTrue($parentIndex < $childIndex, "Parent entity must appear before child entity in the file");
                }
            }

            $this->assertEquals("application/x-ndjson", $contentType);

            return true;

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
            Horus::getInstance()->getEntityMapper()
        );
        $this->generateDataSyncJob->handle($this->getDataEntitiesUseCase,
            $this->syncJobRepository,
            $this->fileHandler,
            $this->config
        );
    }

    public function testGenerateDataWithSampleIsSuccess()
    {
        // Given
        $sample = json_decode(file_get_contents(package_path("tests/Unit/sample_sync_data.json")), true);
        $countEntitiesExpected = 1599; // This is the expected count of entities in the sample file

        $userAuth = new UserAuth($this->faker->uuid);
        $syncJob = new SyncJob($this->faker->uuid, $userAuth->getEffectiveUserId(), SyncJobStatus::PENDING);


        $fileUrlExpected = $this->faker->url;

        $this->getDataEntitiesUseCase->shouldReceive("__invoke")->andReturn($sample);
        $this->config->shouldReceive("getPathFilesSync")->andReturn($this->faker->filePath());
        $dataFileSaved = "";

        // Validate file creation
        $this->fileHandler->shouldReceive("createDownloadableTemporaryFile")->once()->withArgs(function ($pathFile, $data, $contentType) use (&$dataFileSaved) {
            $dataFileSaved = $data;
            $this->assertEquals("application/x-ndjson", $contentType);
            return true;
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
            Horus::getInstance()->getEntityMapper()
        );
        $this->generateDataSyncJob->handle($this->getDataEntitiesUseCase,
            $this->syncJobRepository,
            $this->fileHandler,
            $this->config
        );

        $entities = explode(PHP_EOL, $dataFileSaved);
        $this->assertCount($countEntitiesExpected, $entities);
        $this->assertCount($countEntitiesExpected, array_unique($entities)); // Validate no duplicated

        foreach ($entities as $index => $entity) {
            $this->assertJson($entity);
        }
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
            Horus::getInstance()->getEntityMapper()
        );
        $this->generateDataSyncJob->handle($this->getDataEntitiesUseCase,
            $this->syncJobRepository,
            $this->fileHandler,
            $this->config
        );
    }

} 