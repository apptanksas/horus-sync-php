<?php

namespace Api;

use AppTank\Horus\Core\Auth\AccessLevel;
use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\SyncAction;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Database\SyncQueueActionModel;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\LookupFakeEntity;
use Tests\_Stubs\LookupFakeEntityFactory;
use Tests\_Stubs\ParentFakeEntity;
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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $enumValue = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];
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
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];

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
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeEntity::ATTR_SYNC_DELETED_AT);
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
                "entity" => LookupFakeEntity::getEntityName(),
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
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];

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
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeEntity::ATTR_SYNC_DELETED_AT);

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
        $entityName = ParentFakeEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];

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
        $entityName = ParentFakeEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];

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
        $entityName = ParentFakeEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $valueEnumExpected = ParentFakeEntity::ENUM_VALUES[array_rand(ParentFakeEntity::ENUM_VALUES)];

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
        $this->assertDatabaseCount(ParentFakeEntity::getTableName(), 1);
        $this->assertDatabaseHas(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeEntity::getTableName(), [
            ParentFakeEntity::ATTR_SYNC_OWNER_ID => $userId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeEntity::ATTR_SYNC_DELETED_AT);
    }

}