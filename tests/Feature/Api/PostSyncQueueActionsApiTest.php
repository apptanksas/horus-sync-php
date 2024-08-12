<?php

namespace Api;

use AppTank\Horus\HorusContainer;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ParentFakeEntity;
use Tests\Feature\Api\ApiTestCase;

class PostSyncQueueActionsApiTest extends ApiTestCase
{

    use RefreshDatabase;

    function testPostSyncQueueInsertIsFailureByUnauthorized()
    {
        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value));

        // Then
        $response->assertUnauthorized();
    }

    function testPostSyncQueueInsertIsFailureByBadRequest()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $data = [
            [
                "action" => "INSERT"
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertBadRequest();
    }

    function testPostSyncQueueIsFailureByInvalidAttributes()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "invalid" => $color
                ],
                "actioned_at" => $actionedAt
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertBadRequest();
    }

    function testPostSyncQueueInsertIsSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color
                ],
                "actioned_at" => $actionedAt
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $name,
            'color' => $color
        ]);
    }

    function testPostSyncQueueMultipleIsSuccess()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;

        $data = [
            // delete action
            [
                "action" => "DELETE",
                "entity" => $entityName,
                "data" => ["id" => $entityId],
                "actioned_at" => $actionedAt
            ],
            // update action
            [
                "action" => "UPDATE",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "attributes" => [
                        "name" => $nameExpected,
                        "color" => $colorExpected
                    ]
                ],
                "actioned_at" => $actionedAt - 1000
            ],
            // insert action
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color
                ],
                "actioned_at" => $actionedAt - 2000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertAccepted();
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected
        ]);
        $this->assertSoftDeleted(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeEntity::ATTR_SYNC_DELETED_AT);
    }

}