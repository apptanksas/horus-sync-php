<?php

namespace Api;

use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\SyncFileUploadedModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetWrapperFileApiTest extends ApiTestCase
{
    use RefreshDatabase;

    function testGetFileImageIsSuccess()
    {
        // Given
        $imageUrl = "https://images.unsplash.com/photo-1471502041392-3182a8a099a5?q=80&w=1740&auto=format&fit=crop";
        $fileUploaded = SyncFileUploadedModelFactory::create(fileUrl: $imageUrl);

        // When
        $response = $this->getJson(route(RouteName::GET_WRAPPER_FILE->value, ["id" => $fileUploaded->getId()]));

        // Then
        $response->assertOk();
        $response->assertHeader("Content-Type", "image/jpeg");
    }

    function testGetFilePdfIsSuccess()
    {
        // Given
        $pdfUrl = "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf";
        $fileUploaded = SyncFileUploadedModelFactory::create(fileUrl: $pdfUrl);

        // When
        $response = $this->getJson(route(RouteName::GET_WRAPPER_FILE->value, ["id" => $fileUploaded->getId()]));

        // Then
        $response->assertOk();
        $response->assertHeader("Content-Type", "application/pdf; qs=0.001");
    }

    function testGetFileIsNotFound()
    {
        // Given
        $fileId = $this->faker->uuid;

        // When
        $response = $this->getJson(route(RouteName::GET_WRAPPER_FILE->value, ["id" => $fileId]));

        // Then
        $response->assertNotFound();
    }
}