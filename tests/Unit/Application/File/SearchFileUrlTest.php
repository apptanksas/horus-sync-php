<?php

namespace Tests\Unit\Application\File;

use AppTank\Horus\Application\File\SearchFileUrl;
use AppTank\Horus\Core\Exception\FileNotFoundException;
use AppTank\Horus\Core\Repository\FileUploadedRepository;
use Mockery\Mock;
use Tests\_Stubs\FileUploadedFactory;
use Tests\TestCase;

class SearchFileUrlTest extends TestCase
{
    private FileUploadedRepository|Mock $fileUploadedRepository;

    private SearchFileUrl $searchFileUrl;

    function setUp(): void
    {
        parent::setUp();

        $this->fileUploadedRepository = $this->mock(FileUploadedRepository::class);
        $this->searchFileUrl = new SearchFileUrl($this->fileUploadedRepository);
    }

    function testInvokeIsSuccess()
    {
        // Given
        $fileUploadedExpected = FileUploadedFactory::create();

        $this->fileUploadedRepository->shouldReceive('search')->once()
            ->with($fileUploadedExpected->id)->andReturn($fileUploadedExpected);

        // When
        $result = $this->searchFileUrl->__invoke($fileUploadedExpected->id);

        // Then
        $this->assertEquals($fileUploadedExpected->publicUrl, $result["url"]);
        $this->assertEquals($fileUploadedExpected->mimeType, $result["mime_type"]);
    }

    function testInvokeThrowException()
    {
        // Given
        $fileUploadedExpected = FileUploadedFactory::create();

        $this->fileUploadedRepository->shouldReceive('search')->once()
            ->with($fileUploadedExpected->id)->andReturn(null);

        // When
        $this->expectException(FileNotFoundException::class);
        $this->searchFileUrl->__invoke($fileUploadedExpected->id);
    }
}
