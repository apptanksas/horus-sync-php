<?php

namespace Tests\_Stubs\Client;

use AppTank\Horus\Client\HorusQueueActionClient;
use AppTank\Horus\Core\SyncAction;
use Tests\_Stubs\SyncQueueActionModelFactory;
use Tests\TestCase;

class HorusQueueActionClientTest extends TestCase
{
    function testGetIsLastActionSuccess()
    {

        $action = SyncQueueActionModelFactory::create();

        // When
        $result = HorusQueueActionClient::getLastActionByEntity(SyncAction::from($action->getAction()), $action->getEntity(), $action->getEntityId());

        // Then
        $this->assertNotNull($result);
        $this->assertEquals($action->action, $result->action->value());
        $this->assertEquals($action->getEntity(), $result->entity);
        $this->assertEquals($action->getEntityId(), $result->entityId);
    }

    function testGetIsLastActionFail()
    {
        // Given
        $action = SyncQueueActionModelFactory::create();

        // When
        $result = HorusQueueActionClient::getLastActionByEntity(SyncAction::from($action->getAction()), 'fake_entity', 'fake_id');

        // Then
        $this->assertNull($result);
    }
}
