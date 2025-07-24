<?php

namespace Tests\Unit\Repository;

use AppTank\Horus\Illuminate\Database\SyncJobModel;
use AppTank\Horus\Repository\EloquentSyncJobRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\SyncJobFactory;
use Tests\_Stubs\SyncJobModelFactory;
use Tests\TestCase;

class EloquentSyncJobRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentSyncJobRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentSyncJobRepository();
    }

    public function testSaveCreateNewItemIsSuccess()
    {
        // Given
        $syncJob = SyncJobFactory::create();

        // When
        $this->repository->save($syncJob);

        // Then
        $this->assertDatabaseHas(SyncJobModel::TABLE_NAME, [
            SyncJobModel::ATTR_ID => $syncJob->id,
            SyncJobModel::FK_USER_ID => $syncJob->userId,
            SyncJobModel::ATTR_STATUS => $syncJob->status->value,
            SyncJobModel::ATTR_DOWNLOAD_URL => $syncJob->downloadUrl,
        ]);
    }

    public function testSaveUpdateItemIsSuccess()
    {
        // Given
        $syncJobModel = SyncJobModelFactory::create();
        $syncJob = SyncJobFactory::create($syncJobModel->getId(), $syncJobModel->getUserId());

        // When
        $this->repository->save($syncJob);

        // Then
        $this->assertDatabaseHas(SyncJobModel::TABLE_NAME, [
            SyncJobModel::ATTR_ID => $syncJob->id,
            SyncJobModel::FK_USER_ID => $syncJob->userId,
            SyncJobModel::ATTR_STATUS => $syncJob->status->value,
            SyncJobModel::ATTR_DOWNLOAD_URL => $syncJob->downloadUrl,
        ]);
        $this->assertDatabaseCount(SyncJobModel::TABLE_NAME, 1);
    }

    public function testSearchIsSuccess()
    {
        // Given
        $syncJob = SyncJobFactory::create();
        $this->repository->save($syncJob);

        // When
        $result = $this->repository->search($syncJob->id);

        // Then
        $this->assertEquals($syncJob->id, $result->id);
        $this->assertEquals($syncJob->userId, $result->userId);
        $this->assertEquals($syncJob->status, $result->status);
        $this->assertEquals($syncJob->downloadUrl, $result->downloadUrl);
    }

    public function testSearchIsNull()
    {
        // Given
        $syncJob = SyncJobFactory::create();

        // When
        $result = $this->repository->search($syncJob->id);

        // Then
        $this->assertNull($result);
    }

} 