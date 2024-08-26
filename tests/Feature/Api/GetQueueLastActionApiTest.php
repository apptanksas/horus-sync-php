<?php

namespace Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\Feature\Api\ApiTestCase;

class GetQueueLastActionApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const array JSON_SCHEME = [
        'action',
        'entity',
        'data',
        'actioned_at',
        'synced_at'
    ];

    function testGetLastActionIsSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);
        $this->generateArray(fn() => SyncQueueActionModelFactory::create(userId: $userId));

        // When
        $response = $this->get(route(RouteName::GET_SYNC_QUEUE_LAST_ACTION->value));

        // Then
        $response->assertOk();
        $response->assertExactJsonStructure(self::JSON_SCHEME);
    }
}