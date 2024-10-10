<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\File\IFileHandler;
use AppTank\Horus\Core\File\MimeType;
use AppTank\Horus\Horus;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\Mock;
use Tests\_Stubs\FileUploadedFactory;
use Tests\Feature\Api\ApiTestCase;

class UploadFileApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private IFileHandler|Mock $fileHandler;

    function setUp(): void
    {
        parent::setUp();

        $this->fileHandler = $this->mock(IFileHandler::class);
        $this->app->bind(IFileHandler::class, fn() => $this->fileHandler);
    }

    function testUploadFileIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        $file = UploadedFile::fake()->image('avatar.jpg');
        $fileUploadedExpected = FileUploadedFactory::create($userId);
        $fileId = $fileUploadedExpected->id;
        $this->fileHandler->shouldReceive('upload')->once()->andReturn($fileUploadedExpected);
        $this->fileHandler->shouldReceive('getMimeTypesAllowed')->once()->andReturn(MimeType::IMAGES);

        // When
        $response = $this->postJson(route(RouteName::POST_UPLOAD_FILE->value),["id" => $fileId, "file" =>$file ]);

        // Then
        $response->assertOk();
        $response->assertJsonStructure(["url"]);
    }
}