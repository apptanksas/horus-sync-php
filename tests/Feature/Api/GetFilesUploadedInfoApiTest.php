<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncFileUploadedModel;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetFilesUploadedInfoApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        '*' => [
            'id',
            'url',
            'mime_type',
            "status"
        ]
    ];

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