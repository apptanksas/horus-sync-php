<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Mock;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetFilesUploadedInfoApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private IFileHandler|Mock $fileHandler;

    private const array JSON_SCHEME = [
        '*' => [
            'id',
            'url',
            'mime_type',
            "status"
        ]
    ];


    function setUp(): void
    {
        parent::setUp();

        $this->fileHandler = $this->mock(IFileHandler::class);
        $this->app->bind(IFileHandler::class, fn() => $this->fileHandler);
    }

    function testGetFilesUploadedIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $filesUploaded = $this->generateArray(function () use ($userId) {
            return SyncFileUploadedModelFactory::create($userId);
        });

        $ids = array_map(fn(SyncFileUploadedModel $model) => $model->getId(), $filesUploaded);

        // When
        $response = $this->postJson(route(RouteName::POST_GET_UPLOADED_FILES->value), ["ids" => $ids]);

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($ids));
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }
}