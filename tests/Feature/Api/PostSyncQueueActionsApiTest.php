<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\MaxCountEntityRestriction;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\Illuminate\Http\Controller;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\LookupFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
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

    function testPostSyncQueueIsFailureByInvalidAttribute()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
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
                    "color" => $color,
                    "value_enum" => strval(rand(1, 100))
                ],
                "actioned_at" => $actionedAt
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertBadRequest();
        $response->assertJson([
            "message" => sprintf(Controller::ERROR_MESSAGE_ATTRIBUTE_INVALID, "value_enum")
        ]);
    }

    function testPostSyncQueueInsertIsSuccess()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $enumValue = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color,
                    "value_enum" => $enumValue
                ],
                "actioned_at" => $actionedAt
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $name,
            'color' => $color
        ]);
    }

    function testPostSyncQueueMultipleIsSuccess()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

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
                        "color" => $colorExpected,
                        "value_enum" => $valueEnumExpected
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
                    "color" => $color,
                    "value_enum" => $valueEnum
                ],
                "actioned_at" => $actionedAt - 2000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertAccepted();
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeWritableEntity::ATTR_SYNC_DELETED_AT);
    }

    function testPostSyncQueueLookupIsFailure()
    {
        $ownerId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($ownerId));
        $this->generateArray(fn() => LookupFakeEntityFactory::create());
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            // delete action
            [
                "action" => "INSERT",
                "entity" => ReadableFakeEntity::getEntityName(),
                "data" => ["id" => $this->faker->randomNumber(), "name" => $this->faker->name],
                "actioned_at" => $actionedAt
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertUnauthorized();
    }

    function testPostSyncQueueWithUserActingAsIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userId = $this->faker->uuid;

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userId,
                [new EntityGranted($userOwnerId,
                    new EntityReference($entityName, $entityId), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));


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
                        "color" => $colorExpected,
                        "value_enum" => $valueEnumExpected
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
                    "color" => $color,
                    "value_enum" => $valueEnum
                ],
                "actioned_at" => $actionedAt - 2000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then

        $response->assertAccepted();
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeWritableEntity::ATTR_SYNC_DELETED_AT);

        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userOwnerId,
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::INSERT->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName
        ]);


        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userOwnerId,
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName
        ]);


        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userOwnerId,
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::DELETE->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName
        ]);
    }

    function testTryUpdateEntityButNotPermission()
    {
        $userOwnerId = $this->faker->uuid;
        $userId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerId);
        $entityId = $entity->getId();
        $entityName = ParentFakeWritableEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userId,
                [new EntityGranted($userOwnerId,
                    new EntityReference($entityName, $entityId), AccessLevel::new(Permission::DELETE))
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));


        $data = [
            // update action
            [
                "action" => "UPDATE",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "attributes" => [
                        "name" => $nameExpected,
                        "color" => $colorExpected,
                        "value_enum" => $valueEnumExpected
                    ]
                ],
                "actioned_at" => $actionedAt - 1000
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertUnauthorized();
    }

    function testTryUpdateEntityButNotPermissionByActingAsNotMatchWithEntityGranted()
    {
        $userOwnerRealId = $this->faker->uuid;
        $userOwnerId = $this->faker->uuid;
        $userId = $this->faker->uuid;

        $entity = ParentFakeEntityFactory::create($userOwnerRealId);
        $entityId = $entity->getId();
        $entityName = ParentFakeWritableEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userId,
                [new EntityGranted($userOwnerId,
                    new EntityReference($entityName, $entityId), AccessLevel::new(Permission::UPDATE))
                ], new UserActingAs($userOwnerRealId))
        )->setConfig(new Config(true));


        $data = [
            // update action
            [
                "action" => "UPDATE",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "attributes" => [
                        "name" => $nameExpected,
                        "color" => $colorExpected,
                        "value_enum" => $valueEnumExpected
                    ]
                ],
                "actioned_at" => $actionedAt - 1000
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertUnauthorized();
    }

    function testActionWithEntityNotFoundShouldBadRequest()
    {

        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = $this->faker->colorName;
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
        $response->assertBadRequest();
    }

    function testPostSyncQueueWithNewEntityAndUpdateItIsSuccess()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId))->setConfig(new Config(true));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        $data = [
            // delete action
            [
                "action" => "DELETE",
                "entity" => $entityName,
                "data" => ["id" => $entityId],
                "actioned_at" => 1725037164
            ],
            // update action
            [
                "action" => "UPDATE",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "attributes" => [
                        "name" => $nameExpected,
                        "color" => $colorExpected,
                        "value_enum" => $valueEnumExpected
                    ]
                ],
                "actioned_at" => 1725037064
            ],
            // insert action
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color,
                    "value_enum" => $valueEnum
                ],
                "actioned_at" => 1725037000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertAccepted();
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeWritableEntity::ATTR_SYNC_DELETED_AT);
    }

    function testPostSyncQueueIsBadRequestByEntityExceeded()
    {
        $userId = $this->faker->uuid;

        $entities = $this->generateArray(fn() => ParentFakeEntityFactory::create($userId));

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true))
            ->setEntityRestrictions([
                new MaxCountEntityRestriction(ParentFakeWritableEntity::getEntityName(), count($entities))
            ]);

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        $data = [
            // insert action
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color,
                    "value_enum" => $valueEnum
                ],
                "actioned_at" => 1725037000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertBadRequest();
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), count($entities));
    }

}