<?php

namespace Client;

use AppTank\Horus\Client\IHorusQueueActionClient;
use AppTank\Horus\Core\Factory\EntityOperationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\_Stubs\QueueActionFactory;
use Tests\Feature\Api\ApiTestCase;

class PushActionClientTest extends ApiTestCase
{

    use RefreshDatabase;

    function testPushActionsInsert()
    {
        // Given
        $horusActionClient = $this->app->make(IHorusQueueActionClient::class);

        $insertActions = $this->generateArray(function () use (&$filesUploaded) {
            $parentData = ParentFakeEntityFactory::newData();
            return QueueActionFactory::create(
                EntityOperationFactory::createEntityInsert(
                    $this->faker->uuid,
                    ParentFakeWritableEntity::getEntityName(), $parentData, now()->toDateTimeImmutable()
                )
            );
        });

        // When
        $horusActionClient->pushActions(...$insertActions);

        // Then
        $this->assertDatabaseCount(ParentFakeWritableEntity::getTableName(), count($insertActions));
    }

}