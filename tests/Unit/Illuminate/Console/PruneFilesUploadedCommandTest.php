<?php

namespace Tests\Unit\Illuminate\Console;

use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Console\PruneFilesUploadedCommand;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
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

        $recordsParent = $this->generateArray(function () use ($createdAt) {
            return ParentFakeEntityFactory::create(data: [
                ParentFakeWritableEntity::ATTR_IMAGE => $this->faker->uuid
            ]);
        });

        foreach ($recordsParent as $record) {
           $record->delete();
        }

        $this->fileHandler->shouldReceive('delete')->times(count($filesUploaded))->andReturn(true);


        $this->artisan(PruneFilesUploadedCommand::COMMAND_NAME)
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount(SyncFileUploadedModel::TABLE_NAME, 0);
    }
}
