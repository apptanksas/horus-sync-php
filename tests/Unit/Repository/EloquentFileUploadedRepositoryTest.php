<?php

namespace Tests\Unit\Repository;

use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use AppTank\Horus\Repository\EloquentFileUploadedRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\FileUploadedFactory;
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

    public function testSaveIsSuccess()
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
