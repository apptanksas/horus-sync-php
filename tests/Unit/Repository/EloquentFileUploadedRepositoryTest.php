<?php

namespace Tests\Unit\Repository;

use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use AppTank\Horus\Repository\EloquentFileUploadedRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\FileUploadedFactory;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\TestCase;

class EloquentFileUploadedRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentFileUploadedRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentFileUploadedRepository();
    }

    public function testSaveCreateNewItemIsSuccess()
    {
        // Given
        $file = FileUploadedFactory::create();

        // When
        $this->repository->save($file);

        // Then
        $this->assertDatabaseHas(SyncFileUploadedModel::TABLE_NAME, [
            SyncFileUploadedModel::ATTR_ID => $file->id,
            SyncFileUploadedModel::ATTR_MIME_TYPE => $file->mimeType,
            SyncFileUploadedModel::ATTR_PATH => $file->path,
            SyncFileUploadedModel::ATTR_PUBLIC_URL => $file->publicUrl,
            SyncFileUploadedModel::FK_OWNER_ID => $file->ownerId,
        ]);
    }

    public function testSaveUpdateItemIsSuccess()
    {
        // Given
        $fileUploadedModel = SyncFileUploadedModelFactory::create();
        $file = FileUploadedFactory::create($fileUploadedModel->getId(), $fileUploadedModel->getOwnerId());

        // When
        $this->repository->save($file);

        // Then
        $this->assertDatabaseHas(SyncFileUploadedModel::TABLE_NAME, [
            SyncFileUploadedModel::ATTR_ID => $file->id,
            SyncFileUploadedModel::ATTR_MIME_TYPE => $file->mimeType,
            SyncFileUploadedModel::ATTR_PATH => $file->path,
            SyncFileUploadedModel::ATTR_PUBLIC_URL => $file->publicUrl,
            SyncFileUploadedModel::FK_OWNER_ID => $file->ownerId,
        ]);
        $this->assertDatabaseCount(SyncFileUploadedModel::TABLE_NAME, 1);
    }

    public function testSearchIsSuccess()
    {
        // Given
        $file = FileUploadedFactory::create();
        $this->repository->save($file);

        // When
        $result = $this->repository->search($file->id);

        // Then
        $this->assertEquals($file->id, $result->id);
        $this->assertEquals($file->mimeType, $result->mimeType);
        $this->assertEquals($file->path, $result->path);
        $this->assertEquals($file->publicUrl, $result->publicUrl);
        $this->assertEquals($file->ownerId, $result->ownerId);
    }

    public function testSearchInBatchIsSuccess()
    {
        $userId = $this->faker->uuid;

        $filesUploaded = $this->generateArray(function () use ($userId) {
            return SyncFileUploadedModelFactory::create($userId);
        });

        $ids = array_map(fn(SyncFileUploadedModel $model) => $model->getId(), $filesUploaded);

        // When
        $result = $this->repository->searchInBatch($userId, $ids);

        // Then
        $this->assertCount(count($ids), $result);
    }

    public function testSearchIsNull()
    {
        // Given
        $file = FileUploadedFactory::create();

        // When
        $result = $this->repository->search($file->id);

        // Then
        $this->assertNull($result);
    }

    public function testDeleteIsSuccess()
    {
        // Given
        $file = FileUploadedFactory::create();
        $this->repository->save($file);

        // When
        $this->repository->delete($file->id);

        // Then
        $this->assertDatabaseMissing(SyncFileUploadedModel::TABLE_NAME, [
            SyncFileUploadedModel::ATTR_ID => $file->id,
        ]);
    }

}
