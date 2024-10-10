<?php

namespace Application\File;

use AppTank\Horus\Application\File\UploadFile;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Exception\UploadFileException;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use Illuminate\Http\UploadedFile;
use Mockery\Mock;
use Tests\_Stubs\FileUploadedFactory;
use Tests\TestCase;

class UploadFileTest extends TestCase
{
    private IFileHandler|Mock $fileHandler;
    private FileUploadedRepository|Mock $fileUploadedRepository;

    private UploadFile $uploadFile;

    function setUp(): void
    {
        parent::setUp();

        $this->fileHandler = $this->mock(IFileHandler::class);
        $this->fileUploadedRepository = $this->mock(FileUploadedRepository::class);
        $this->uploadFile = new UploadFile($this->fileHandler, $this->fileUploadedRepository);
    }

    function testInvokeIsSuccess()
    {
        // Given
        $userAuth = new UserAuth($this->faker->uuid);
        $file = UploadedFile::fake()->image('photo.jpg');
        $fileUploadedExpected = FileUploadedFactory::create($userAuth->userId);

        $this->fileHandler->shouldReceive('upload')->once()->with($userAuth->userId, $fileUploadedExpected->id, $file)->andReturn($fileUploadedExpected);
        $this->fileUploadedRepository->shouldReceive('save')->once()->with($fileUploadedExpected);

        // When
        $result = $this->uploadFile->__invoke($userAuth, $fileUploadedExpected->id, $file);

        // Then
        $this->assertEquals($fileUploadedExpected->publicUrl, $result["url"]);
    }

    function testInvokeThrowException()
    {
        // Given
        $userAuth = new UserAuth($this->faker->uuid);
        $file = UploadedFile::fake()->image('photo.jpg');
        $fileUploadedExpected = FileUploadedFactory::create($userAuth->userId);

        $this->fileHandler->shouldReceive('upload')->once()->with($userAuth->userId, $fileUploadedExpected->id, $file)->andReturn($fileUploadedExpected);
        $this->fileUploadedRepository->shouldReceive('save')->once()->with($fileUploadedExpected)->andThrow(new \Exception());
        $this->fileHandler->shouldReceive('delete')->once()->with($fileUploadedExpected);

        // When
        $this->expectException(UploadFileException::class);
        $this->uploadFile->__invoke($userAuth, $fileUploadedExpected->id, $file);
    }
}
