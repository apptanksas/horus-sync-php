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
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\NestedChildFakeEntityFactory;
use Tests\_Stubs\NestedChildFakeWritableEntity;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ReadableFakeEntityFactory;
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
        $timestamp = $this->faker->dateTimeBetween->getTimestamp();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color,
                    "timestamp" => $timestamp,
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
        $timestamp = 1674579600;
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color,
                    "timestamp" => $timestamp,
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
            'color' => $color,
            'timestamp' => $this->getDateTimeUtil()->getFormatDate($timestamp),
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
        $timestampExpected = $this->faker->dateTimeBetween->getTimestamp();
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
                        "timestamp" => $timestampExpected,
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
                    "timestamp" => $timestampExpected,
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
            'timestamp' => $this->getDateTimeUtil()->getFormatDate($timestampExpected),
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
        $this->generateArray(fn() => ReadableFakeEntityFactory::create());
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

    function testPostSyncQueueWithUserActingAsWithChildEntityIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userId = $this->faker->uuid;

        $parent = ParentFakeEntityFactory::create($userOwnerId);
        $childrenData = ChildFakeEntityFactory::newData($parent->getId());

        $entityId = $childrenData['id'];
        $entityName = ChildFakeWritableEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $floatValueExpected = $this->faker->randomFloat();
        $colorExpected = $this->faker->colorName;

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parent->getId()), AccessLevel::all())
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
                        ChildFakeWritableEntity::ATTR_FLOAT_VALUE => $floatValueExpected,
                        ChildFakeWritableEntity::ATTR_STRING_VALUE => $colorExpected,
                    ]
                ],
                "actioned_at" => $actionedAt - 1000
            ],
            // insert action
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => $childrenData,
                "actioned_at" => $actionedAt - 2000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then

        $response->assertAccepted();
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
            'id' => $entityId,
            ChildFakeWritableEntity::ATTR_FLOAT_VALUE => $floatValueExpected,
            ChildFakeWritableEntity::ATTR_STRING_VALUE => $colorExpected,
        ]);
        $this->assertSoftDeleted(ChildFakeWritableEntity::getTableName(), [
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
            'id' => $entityId
        ], deletedAtColumn: ChildFakeWritableEntity::ATTR_SYNC_DELETED_AT);

        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userOwnerId,
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::INSERT->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName,
            SyncQueueActionModel::ATTR_ENTITY_ID => $entityId,
        ]);


        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userOwnerId,
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName,
            SyncQueueActionModel::ATTR_ENTITY_ID => $entityId,
        ]);


        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userOwnerId,
            SyncQueueActionModel::FK_USER_ID => $userId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::DELETE->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName,
            SyncQueueActionModel::ATTR_ENTITY_ID => $entityId,
        ]);
    }

    function testPostSyncQueueWithUserActingAsWithParentEntityIsSuccess()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $valueEnum = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $nameExpected = $this->faker->userName;
        $colorExpected = $this->faker->colorName;
        $timestampExpected = $this->faker->dateTimeBetween->getTimestamp();
        $valueEnumExpected = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];

        $entityAnotherGranted = [new EntityGranted($userOwnerId,
            new EntityReference($entityName, ParentFakeEntityFactory::create($userOwnerId)->getId()), AccessLevel::all())
        ];

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId, $entityAnotherGranted, new UserActingAs($userOwnerId))
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
                    "timestamp" => $timestampExpected,
                    "value_enum" => $valueEnum
                ],
                "actioned_at" => $actionedAt - 2000
            ],
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then

        $response->assertAccepted();
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), 2);
        $this->assertDatabaseHas(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userGuestId,
            'id' => $entityId,
            'name' => $nameExpected,
            'color' => $colorExpected,
            'value_enum' => $valueEnumExpected
        ]);
        $this->assertSoftDeleted(ParentFakeWritableEntity::getTableName(), [
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userGuestId,
            'id' => $entityId
        ], deletedAtColumn: ParentFakeWritableEntity::ATTR_SYNC_DELETED_AT);

        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userGuestId,
            SyncQueueActionModel::FK_USER_ID => $userGuestId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::INSERT->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName,
            SyncQueueActionModel::ATTR_ENTITY_ID => $entityId,
        ]);


        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userGuestId,
            SyncQueueActionModel::FK_USER_ID => $userGuestId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::UPDATE->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName,
            SyncQueueActionModel::ATTR_ENTITY_ID => $entityId,
        ]);


        $this->assertDatabaseHas(SyncQueueActionModel::TABLE_NAME, [
            SyncQueueActionModel::FK_OWNER_ID => $userGuestId,
            SyncQueueActionModel::FK_USER_ID => $userGuestId,
            SyncQueueActionModel::ATTR_ACTION => SyncAction::DELETE->value(),
            SyncQueueActionModel::ATTR_ENTITY => $entityName,
            SyncQueueActionModel::ATTR_ENTITY_ID => $entityId,
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
        $timestampExpected = $this->faker->dateTimeBetween->getTimestamp();
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
                        "timestamp" => $timestampExpected,
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
                    "timestamp" => $timestampExpected,
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
        $timestamp = $this->faker->dateTimeBetween->getTimestamp();
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
                    "timestamp" => $timestamp,
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

    function testPostSyncQueueInsertIsSuccessWithChildrenAsOwner()
    {
        $userId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $parent = ParentFakeEntityFactory::create($userId);
        $childrenData = ChildFakeEntityFactory::newData($parent->getId());
        $entityId = $childrenData['id'];
        $entityName = ChildFakeWritableEntity::getEntityName();

        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => $childrenData,
                "actioned_at" => $actionedAt
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityId,
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userId,
        ]);
    }

    function testPostSyncQueueInsertIsSuccessWithChildrenAsGuest()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userGuestId));

        $parent = ParentFakeEntityFactory::create($userOwnerId);
        $childrenData = ChildFakeEntityFactory::newData($parent->getId());
        $entityId = $childrenData['id'];
        $entityName = ChildFakeWritableEntity::getEntityName();

        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parent->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => $childrenData,
                "actioned_at" => $actionedAt
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityId,
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
        ]);
    }

    function testPostSyncQueueInsertIsSuccessWithParentAsGuestAuthenticatedAndInsertedAsOwnerInPrimaryEntity()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $entityId = $this->faker->uuid;
        $entityName = ParentFakeWritableEntity::getEntityName();
        $name = $this->faker->userName;
        $color = $this->faker->colorName;
        $enumValue = ParentFakeWritableEntity::ENUM_VALUES[array_rand(ParentFakeWritableEntity::ENUM_VALUES)];
        $timestamp = 1674579600;
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference($entityName, $entityId), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => [
                    "id" => $entityId,
                    "name" => $name,
                    "color" => $color,
                    "timestamp" => $timestamp,
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
            ParentFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userGuestId,
            'id' => $entityId,
            'name' => $name,
            'color' => $color,
            'timestamp' => $this->getDateTimeUtil()->getFormatDate($timestamp),
        ]);
    }

    function testPostSyncQueueInsertOwnEntityUsingActingAs()
    {
        $userOwnerId = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $parentGuest = ParentFakeEntityFactory::create($userGuestId);

        $childrenGuestData = ChildFakeEntityFactory::newData($parentGuest->getId());
        $childrenOwnerData = ChildFakeEntityFactory::newData($parentOwner->getId());
        $entityGuestId = $childrenGuestData['id'];
        $entityOwnerId = $childrenOwnerData['id'];


        $entityName = ChildFakeWritableEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [new EntityGranted($userOwnerId,
                    new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all())
                ], new UserActingAs($userOwnerId))
        )->setConfig(new Config(true));

        $data = [
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => $childrenGuestData,
                "actioned_at" => $actionedAt
            ],
            [
                "action" => "INSERT",
                "entity" => $entityName,
                "data" => $childrenOwnerData,
                "actioned_at" => $actionedAt + 1000
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), 2);

        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityOwnerId,
            ChildFakeWritableEntity::FK_PARENT_ID => $parentOwner->getId(),
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
        ]);

        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityGuestId,
            ChildFakeWritableEntity::FK_PARENT_ID => $parentGuest->getId(),
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userGuestId,
        ]);

    }

    function testPostSyncQueueInsertWhenParentAndChildAreInTheInsertActions()
    {
        $userOwnerId = $this->faker->uuid;

        $parentData = ParentFakeEntityFactory::newData($userOwnerId);
        unset($parentData[ParentFakeWritableEntity::ATTR_IMAGE]);

        $childData = ChildFakeEntityFactory::newData($parentData['id']);

        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userOwnerId))->setConfig(new Config(true));

        $data = [
            [
                "action" => "INSERT",
                "entity" => ParentFakeWritableEntity::getEntityName(),
                "data" => $parentData,
                "actioned_at" => $actionedAt
            ],
            [
                "action" => "INSERT",
                "entity" => ChildFakeWritableEntity::getEntityName(),
                "data" => $childData,
                "actioned_at" => $actionedAt - 1 // Validate that child is inserted before parent to verify the sorting of actions
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), 1);
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), 1);
    }

    function testPostActionWithDifferentOwnersUsingActingAs()
    {
        $userOwnerId = $this->faker->uuid;
        $userOwnerId2 = $this->faker->uuid;
        $userGuestId = $this->faker->uuid;

        $parentOwner = ParentFakeEntityFactory::create($userOwnerId);
        $parentOwner2 = ParentFakeEntityFactory::create($userOwnerId2);
        $parentGuest = ParentFakeEntityFactory::create($userGuestId);

        $childrenGuestData = ChildFakeEntityFactory::newData($parentGuest->getId());
        $childrenOwnerData = ChildFakeEntityFactory::newData($parentOwner->getId());
        $nestedChildrenOwnerData = NestedChildFakeEntityFactory::newData($childrenOwnerData['id']);
        $childrenOwnerData2 = ChildFakeEntityFactory::newData($parentOwner2->getId());
        $nestedChildrenOwnerData2 = NestedChildFakeEntityFactory::newData($childrenOwnerData2['id']);

        $entityGuestId = $childrenGuestData['id'];
        $entityOwnerId = $childrenOwnerData['id'];
        $entityOwnerId2 = $childrenOwnerData2['id'];

        $childEntityName = ChildFakeWritableEntity::getEntityName();
        $nestedChildEntityName = NestedChildFakeWritableEntity::getEntityName();
        $actionedAt = $this->faker->dateTimeBetween->getTimestamp();

        Horus::getInstance()->setUserAuthenticated(
            new UserAuth($userGuestId,
                [
                    new EntityGranted($userOwnerId,
                        new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner->getId()), AccessLevel::all()
                    ),
                    new EntityGranted($userOwnerId2,
                        new EntityReference(ParentFakeWritableEntity::getEntityName(), $parentOwner2->getId()), AccessLevel::all()
                    )
                ], new UserActingAs($userOwnerId2))
        )->setConfig(new Config(true));

        $data = [
            [
                "action" => "INSERT",
                "entity" => $childEntityName,
                "data" => $childrenGuestData,
                "actioned_at" => $actionedAt
            ],
            [
                "action" => "INSERT",
                "entity" => $childEntityName,
                "data" => $childrenOwnerData,
                "actioned_at" => $actionedAt + 1000
            ],
            [
                "action" => "INSERT",
                "entity" => $childEntityName,
                "data" => $childrenOwnerData2,
                "actioned_at" => $actionedAt + 2000
            ],
            [
                "action" => "INSERT",
                "entity" => $nestedChildEntityName,
                "data" => $nestedChildrenOwnerData,
                "actioned_at" => $actionedAt + 3000
            ],
            [
                "action" => "INSERT",
                "entity" => $nestedChildEntityName,
                "data" => $nestedChildrenOwnerData2,
                "actioned_at" => $actionedAt + 4000
            ]
        ];

        // When
        $response = $this->post(route(RouteName::POST_SYNC_QUEUE_ACTIONS->value), $data);

        // Then
        $response->assertStatus(202);
        $this->assertDatabaseCount(ChildFakeWritableEntity::getTableName(), 3);
        $this->assertDatabaseCount(NestedChildFakeWritableEntity::getTableName(), 2);

        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityOwnerId,
            ChildFakeWritableEntity::FK_PARENT_ID => $parentOwner->getId(),
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
        ]);

        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityGuestId,
            ChildFakeWritableEntity::FK_PARENT_ID => $parentGuest->getId(),
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userGuestId,
        ]);

        $this->assertDatabaseHas(ChildFakeWritableEntity::getTableName(), [
            'id' => $entityOwnerId2,
            ChildFakeWritableEntity::FK_PARENT_ID => $parentOwner2->getId(),
            ChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId2,
        ]);

        $this->assertDatabaseHas(NestedChildFakeWritableEntity::getTableName(), [
            'id' => $nestedChildrenOwnerData['id'],
            NestedChildFakeWritableEntity::FK_CHILD_ID => $childrenOwnerData['id'],
            NestedChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId,
        ]);

        $this->assertDatabaseHas(NestedChildFakeWritableEntity::getTableName(), [
            'id' => $nestedChildrenOwnerData2['id'],
            NestedChildFakeWritableEntity::FK_CHILD_ID => $childrenOwnerData2['id'],
            NestedChildFakeWritableEntity::ATTR_SYNC_OWNER_ID => $userOwnerId2,
        ]);
    }
}