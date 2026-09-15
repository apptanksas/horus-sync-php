<?php

namespace Tests\Unit\Illuminate\Http\Controller\Data;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Http\Controller\Data\GetQueueActionsController;
use Illuminate\Http\Request;
use Tests\_Stubs\QueueActionFactory;
use Tests\TestCase;

class GetQueueActionsControllerTest extends TestCase
{
    function testInvokeWithLimitParameter()
    {
        // Given
        $userId = $this->faker->uuid;
        $limit = 5;
        $action = QueueActionFactory::create(userId: $userId);

        $queueActionRepository = $this->mock(QueueActionRepository::class);
        $accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $entityMapper = $this->mock(EntityMapper::class);

        $queueActionRepository->shouldReceive('getActions')
            ->once()
            ->with([$userId], null, [], [], null, [], $limit)
            ->andReturn([$action]);

        $entityMapper->shouldReceive('isPrimaryEntity')
            ->once()
            ->with($action->entity)
            ->andReturn(false);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $controller = new GetQueueActionsController($queueActionRepository, $accessValidatorRepository, $entityMapper);
        $request = Request::create('/queue/actions', 'GET', ['limit' => (string) $limit]);

        // When
        $response = $controller($request);

        // Then
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals($action->eventId, $data[0]['event_id']);
    }

    function testInvokeWithoutLimitParameter()
    {
        // Given
        $userId = $this->faker->uuid;
        $action = QueueActionFactory::create(userId: $userId);

        $queueActionRepository = $this->mock(QueueActionRepository::class);
        $accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $entityMapper = $this->mock(EntityMapper::class);

        $queueActionRepository->shouldReceive('getActions')
            ->once()
            ->with([$userId], null, [], [], null, [], null)
            ->andReturn([$action]);

        $entityMapper->shouldReceive('isPrimaryEntity')
            ->once()
            ->with($action->entity)
            ->andReturn(false);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $controller = new GetQueueActionsController($queueActionRepository, $accessValidatorRepository, $entityMapper);
        $request = Request::create('/queue/actions', 'GET');

        // When
        $response = $controller($request);

        // Then
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
    }

    function testInvokeWithAfterTimestampAndLimitAndExclude()
    {
        // Given
        $userId = $this->faker->uuid;
        $timestamp = 1700000000;
        $excludeTimestamp = 1699999990;
        $limit = 10;
        $action = QueueActionFactory::create(userId: $userId);

        $queueActionRepository = $this->mock(QueueActionRepository::class);
        $accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $entityMapper = $this->mock(EntityMapper::class);

        $queueActionRepository->shouldReceive('getActions')
            ->once()
            ->with([$userId], $timestamp, [$excludeTimestamp], [], null, [], $limit)
            ->andReturn([$action]);

        $entityMapper->shouldReceive('isPrimaryEntity')
            ->once()
            ->with($action->entity)
            ->andReturn(false);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $controller = new GetQueueActionsController($queueActionRepository, $accessValidatorRepository, $entityMapper);
        $request = Request::create('/queue/actions', 'GET', [
            'after' => (string) $timestamp,
            'exclude' => (string) $excludeTimestamp,
            'limit' => (string) $limit,
        ]);

        // When
        $response = $controller($request);

        // Then
        $this->assertEquals(200, $response->getStatusCode());
    }

    function testInvokeWithAfterEventIdAndLimit()
    {
        // Given
        $userId = $this->faker->uuid;
        $afterEventId = $this->faker->uuid;
        $limit = 3;
        $action = QueueActionFactory::create(userId: $userId);

        $queueActionRepository = $this->mock(QueueActionRepository::class);
        $accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $entityMapper = $this->mock(EntityMapper::class);

        $queueActionRepository->shouldReceive('getActions')
            ->once()
            ->with([$userId], null, [], [], $afterEventId, [], $limit)
            ->andReturn([$action]);

        $entityMapper->shouldReceive('isPrimaryEntity')
            ->once()
            ->with($action->entity)
            ->andReturn(false);

        Horus::getInstance()->setUserAuthenticated(new UserAuth($userId));

        $controller = new GetQueueActionsController($queueActionRepository, $accessValidatorRepository, $entityMapper);
        $request = Request::create('/queue/actions', 'GET', [
            'after' => $afterEventId,
            'limit' => (string) $limit,
        ]);

        // When
        $response = $controller($request);

        // Then
        $this->assertEquals(200, $response->getStatusCode());
    }
}
