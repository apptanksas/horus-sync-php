<?php

namespace Api;

use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetFileUploadedInfoApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        'id',
        'url',
        'mime_type',
        "status"
    ];

    function setUp(): void
    {
        parent::setUp();
    }

    function testGetFileUploadedInfoIsSuccess()
    {
        // Given
        $fileUploaded = SyncFileUploadedModelFactory::create();

        // When
        $response = $this->getJson(route(RouteName::GET_UPLOADED_FILE->value, ["id" => $fileUploaded->getId()]));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
        $response->assertJson([
            "url" => $fileUploaded->getPublicUrl(),
            "mime_type" => $fileUploaded->getMimeType(),
            "status" => $fileUploaded->getStatus()
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