<?php

namespace Tests\Unit\Illuminate\Console;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\File\SyncFileStatus;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Console\PruneFilesUploadedCommand;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use AppTank\Horus\Illuminate\Database\WritableEntitySynchronizable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Mock;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\TestCase;

class PruneFilesUploadedCommandTest extends TestCase
{
    use RefreshDatabase;

    private IFileHandler|Mock $fileHandler;


    public function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(Kernel::class, function ($app) {
            $kernel = $app->make(\Illuminate\Foundation\Console\Kernel::class);
            $kernel->addCommands([
                PruneFilesUploadedCommand::class,
            ]);
            return $kernel;
        });


        $this->fileHandler = $this->mock(IFileHandler::class);

        Horus::setFileHandler($this->fileHandler);
    }

    public function testHandle()
    {
        $createdAt = Carbon::now()->subDays(10);

        $filesUploaded = $this->generateArray(function () use ($createdAt) {
            return SyncFileUploadedModelFactory::create(createdAt: $createdAt);
        });

        $recordsParent = $this->generateArray(function () {
            return ParentFakeEntityFactory::create(data: [
                ParentFakeWritableEntity::ATTR_IMAGE => $this->faker->uuid
            ]);
        });

        foreach ($recordsParent as $record) {
            $record->delete();
        }

        $this->fileHandler->shouldReceive('delete')->times(count($filesUploaded))->andReturn(true);

        $this->artisan(PruneFilesUploadedCommand::COMMAND_NAME)->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount(SyncFileUploadedModel::TABLE_NAME, count($filesUploaded));
        $this->assertDatabaseHas(SyncFileUploadedModel::class, [
            SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::DELETED->value()
        ]);
    }

    public function testHandleWitRecordDeletedAt()
    {
        $createdAt = Carbon::now()->subDays(15);
        $countExpected = rand(5, 15);

        $this->generateCountArray(function () use ($createdAt) {

            $imageFileIdReference = $this->faker->uuid;

            SyncFileUploadedModelFactory::create(status: SyncFileStatus::PENDING, createdAt: $createdAt, fileReference: $imageFileIdReference);

            $entity = ParentFakeEntityFactory::create(data: [
                ParentFakeWritableEntity::ATTR_IMAGE => $imageFileIdReference
            ]);

            ParentFakeWritableEntity::query()->where(WritableEntitySynchronizable::ATTR_ID, $entity->getId())->update([ParentFakeWritableEntity::DELETED_AT => Carbon::now()->subDays(12)]);

            return $imageFileIdReference;

        }, $countExpected);

        $this->fileHandler->shouldReceive("delete")->times($countExpected)->andReturn(true);

        // When
        $this->artisan(PruneFilesUploadedCommand::COMMAND_NAME)->assertExitCode(Command::SUCCESS);

        // Then
        $this->assertDatabaseCount(SyncFileUploadedModel::class, $countExpected);
        $this->assertDatabaseHas(SyncFileUploadedModel::class, [
            SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::DELETED->value()
        ]);

    }


    public function testHandleWithValidateFilesPendingWithExtraParameters()
    {
        $createdAt = Carbon::now()->subDays(10);
        $countExpected = rand(5, 15);

        Horus::getInstance()->setConfig(new Config(
            extraParametersReferenceFile: [ParentFakeWritableEntity::getEntityName() => ParentFakeWritableEntity::ATTR_CUSTOM]
        ));

        $this->generateCountArray(function () use ($createdAt) {
            $fileIdReference = $this->faker->uuid;
            $imageFileIdReference = $this->faker->uuid;

            SyncFileUploadedModelFactory::create(status: SyncFileStatus::PENDING, createdAt: $createdAt, fileReference: $fileIdReference);
            SyncFileUploadedModelFactory::create(status: SyncFileStatus::LINKED, createdAt: $createdAt, fileReference: $imageFileIdReference);

            ParentFakeEntityFactory::create(data: [
                ParentFakeWritableEntity::ATTR_CUSTOM => $fileIdReference,
                ParentFakeWritableEntity::ATTR_IMAGE => $imageFileIdReference
            ]);

            return $fileIdReference;

        }, $countExpected);

        $this->fileHandler->shouldReceive("copy")->times($countExpected)->andReturn(true);
        $this->fileHandler->shouldReceive("delete")->times($countExpected)->andReturn(true);
        $this->fileHandler->shouldReceive("generateUrl")->times($countExpected)->andReturn($this->faker->url);

        // When
        $this->artisan(PruneFilesUploadedCommand::COMMAND_NAME)->assertExitCode(Command::SUCCESS);

        // Then
        $this->assertDatabaseCount(SyncFileUploadedModel::class, $countExpected * 2);

        $this->assertDatabaseHas(SyncFileUploadedModel::class, [
            SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::LINKED->value()
        ]);

    }

    public function testHandleWithValidateFilesPendingWithExtraParametersAndFileReferenceParameters()
    {
        $countIteration = rand(5, 15);
        $countExpected = $countIteration * 2;

        Horus::getInstance()->setConfig(new Config(
            extraParametersReferenceFile: [ParentFakeWritableEntity::getEntityName() => ParentFakeWritableEntity::ATTR_CUSTOM]
        ));

        $this->generateCountArray(function () {

            $customFileIdReference = $this->faker->uuid;
            $fileReference = $this->faker->uuid;

            SyncFileUploadedModelFactory::create(status: SyncFileStatus::PENDING, createdAt: Carbon::now()->subDays(rand(1, 10000)), fileReference: $customFileIdReference);
            SyncFileUploadedModelFactory::create(status: SyncFileStatus::PENDING, createdAt: Carbon::now()->subDays(rand(1, 10000)), fileReference: $fileReference);

            ParentFakeEntityFactory::create(data: [
                ParentFakeWritableEntity::ATTR_CUSTOM => $customFileIdReference,
                ParentFakeWritableEntity::ATTR_IMAGE => $fileReference
            ]);

        }, $countIteration);

        $this->fileHandler->shouldReceive("copy")->times($countExpected)->andReturn(true);
        $this->fileHandler->shouldReceive("delete")->times($countExpected)->andReturn(true);
        $this->fileHandler->shouldReceive("generateUrl")->times($countExpected)->andReturn($this->faker->url);

        // When
        $this->artisan(PruneFilesUploadedCommand::COMMAND_NAME)->assertExitCode(Command::SUCCESS);

        // Then
        $this->assertDatabaseCount(ParentFakeWritableEntity::class, $countIteration);
        $this->assertDatabaseCount(SyncFileUploadedModel::class, $countExpected);
        $this->assertDatabaseHas(SyncFileUploadedModel::class, [
            SyncFileUploadedModel::ATTR_STATUS => SyncFileStatus::LINKED->value()
        ]);

    }
}
