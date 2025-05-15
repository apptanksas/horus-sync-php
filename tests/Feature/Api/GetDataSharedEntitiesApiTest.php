<?php

namespace Api;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Horus;
use AppTank\Horus\RouteName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ReadableFakeEntityFactory;
use Tests\_Stubs\ReadableFakeEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\Feature\Api\ApiTestCase;

/**
 * @internal Class GetDataSharedEntitiesApiTest
 *
 * Integration tests for the shared entities data API endpoint.
 * Tests the retrieval of shared entities and caching functionality.
 *
 * @author Based on requirements
 * Year: 2024
 */
class GetDataSharedEntitiesApiTest extends ApiTestCase
{
    use RefreshDatabase;

    /**
     * Test that the shared entities endpoint returns successful response with shared entities.
     */
    function testGetDataSharedEntitiesSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $sharedEntities = [];

        // Create some readable entities that will be shared
        $this->generateArray(function () use (&$sharedEntities) {
            $entity = ReadableFakeEntityFactory::create();
            $sharedEntities[] = new EntityReference(ReadableFakeEntity::getEntityName(), $entity->getId());
            return $entity;
        });

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));
        Horus::getInstance()->setupOnSharedEntities(fn() => $sharedEntities);

        // When
        $response = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($sharedEntities));
        $response->assertJsonStructure([
            '*' => [
                'entity',
                'data' => [
                    'id',
                    'name',
                    "type"
                ]
            ]
        ]);
    }

    /**
     * Test that the shared entities endpoint returns empty array when no shared entities are configured.
     */
    function testGetDataSharedEntitiesEmptySuccess()
    {
        // Given
        $userId = $this->faker->uuid;

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        Horus::getInstance()->setupOnSharedEntities(fn() => []);

        // When
        $response = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(0);
    }

    /**
     * Test that the shared entities endpoint properly combines multiple entity types.
     */
    function testGetDataSharedEntitiesMultipleEntityTypesSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $sharedEntities = [];

        // Create readable entities
        $readableEntities = $this->generateArray(function () use (&$sharedEntities) {
            $entity = ReadableFakeEntityFactory::create();
            $sharedEntities[] = new EntityReference(ReadableFakeEntity::getEntityName(), $entity->getId());
            return $entity;
        }, 3);

        // Create parent entities
        $parentEntities = $this->generateArray(function () use (&$sharedEntities) {
            $entity = ParentFakeEntityFactory::create($this->faker->uuid);
            $sharedEntities[] = new EntityReference(ParentFakeWritableEntity::getEntityName(), $entity->getId());
            return $entity;
        }, 2);

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        Horus::getInstance()->setupOnSharedEntities(fn() => $sharedEntities);

        // When
        $response = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then
        $response->assertOk();
        $response->assertJsonCount(count($sharedEntities));
    }

    /**
     * Test that the shared entities endpoint properly caches responses.
     */
    function testGetDataSharedEntitiesCacheSuccess()
    {
        // Given
        $userId = $this->faker->uuid;
        $sharedEntities = [];

        // Create entities and add them to shared entities
        $readableEntities = $this->generateArray(function () use (&$sharedEntities) {
            $entity = ReadableFakeEntityFactory::create();
            $sharedEntities[] = new EntityReference(ReadableFakeEntity::getEntityName(), $entity->getId());
            return $entity;
        });

        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($userId))
            ->setConfig(new Config(true));

        Horus::getInstance()->setSharedEntities($sharedEntities);

        // When - first request to populate cache
        $response1 = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then - verify first response
        $response1->assertOk();
        $response1->assertJsonCount(count($sharedEntities));

        // When - create more entities but don't add them to shared config
        ReadableFakeEntityFactory::create();

        // Make second request (should be from cache)
        $response2 = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then - second response should match first response (from cache)
        $response2->assertOk();
        $response2->assertJsonCount(count($sharedEntities));

        // Verify content is exactly the same (which means it came from cache)
        $this->assertEquals(
            $response1->getContent(),
            $response2->getContent()
        );
    }

    /**
     * Test that the shared entities endpoint handles different users with different configurations.
     */
    function testGetDataSharedEntitiesDifferentUsersSuccess()
    {
        // Given
        $user1Id = $this->faker->uuid;
        $user2Id = $this->faker->uuid;

        $user1SharedEntities = [];
        $user2SharedEntities = [];

        // Create shared entities for user 1
        $user1Entities = $this->generateArray(function () use (&$user1SharedEntities) {
            $entity = ReadableFakeEntityFactory::create();
            $user1SharedEntities[] = new EntityReference(ReadableFakeEntity::getEntityName(), $entity->getId());
            return $entity;
        }, 2);

        // Create shared entities for user 2
        $user2Entities = $this->generateArray(function () use (&$user2SharedEntities) {
            $entity = ReadableFakeEntityFactory::create();
            $user2SharedEntities[] = new EntityReference(ReadableFakeEntity::getEntityName(), $entity->getId());
            return $entity;
        }, 3);

        // When - test for user 1
        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($user1Id))
            ->setConfig(new Config(true));

        Horus::getInstance()->setupOnSharedEntities(fn() => $user1SharedEntities);

        $response1 = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then - verify user 1 response
        $response1->assertOk();
        $response1->assertJsonCount(count($user1SharedEntities));

        // When - test for user 2
        Horus::getInstance()
            ->setUserAuthenticated(new UserAuth($user2Id))
            ->setConfig(new Config(true));

        Horus::getInstance()->setupOnSharedEntities(fn() => $user2SharedEntities);

        $response2 = $this->get(route(RouteName::GET_DATA_SHARED->value));

        // Then - verify user 2 response
        $response2->assertOk();
        $response2->assertJsonCount(count($user2SharedEntities));
    }

    function testValidateThatSetupOnSharedEntitiesOnlyCallInSharedEntitiesEndpoint()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));
        Horus::getInstance()->setupOnSharedEntities(fn() => throw new \Exception("This should not be called"));

        $this->get(route(RouteName::GET_DATA_ENTITIES->value))->assertOk();
        $this->get(route(RouteName::GET_MIGRATIONS->value))->assertOk();
        $this->get(route(RouteName::GET_ENTITY_DATA->value, [ParentFakeWritableEntity::getEntityName()]))->assertOk();
        $this->get(route(RouteName::GET_DATA_SHARED->value))->assertServerError();
    }
} 