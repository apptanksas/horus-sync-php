<?php

namespace Api;

use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Mock;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetFileUploadedInfoApiTest extends ApiTestCase
{

    use RefreshDatabase;

    private IFileHandler|Mock $fileHandler;

    function setUp(): void
    {
        parent::setUp();

        $this->fileHandler = $this->mock(IFileHandler::class);
        $this->app->bind(IFileHandler::class, fn() => $this->fileHandler);
    }

    function testGetFileUploadedInfoIsSuccess()
    {
        // Given
        $fileUploaded = SyncFileUploadedModelFactory::create();

        // When
        $response = $this->getJson(route(RouteName::GET_UPLOADED_FILE->value, ["id" => $fileUploaded->getId()]));

        // Then
        $response->assertOk();
        $response->assertJsonStructure(["url", "mime_type"]);
        $response->assertJson([
            "url" => $fileUploaded->getPublicUrl(),
            "mime_type" => $fileUploaded->getMimeType()
        ]);
    }

    function testGetFileUploadedInfoIsNotFound()
    {
        // Given
        $fileId = $this->faker->uuid;

        // When
        $response = $this->getJson(route(RouteName::GET_UPLOADED_FILE->value, ["id" => $fileId]));

        // Then
        $response->assertNotFound();
    }
}