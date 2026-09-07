<?php

namespace Tests\Unit\Application\Get;

use AppTank\Horus\Application\Get\GetQueueActions;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Core\Repository\QueueActionRepository;
use Tests\_Stubs\QueueActionFactory;
use Tests\TestCase;

class GetQueueActionsTest extends TestCase
{
    function testInvokePassesEventFiltersToRepository()
    {
        // Given
        $userId = $this->faker->uuid;
        $afterEventId = $this->faker->uuid;
        $excludeEventIds = [$this->faker->uuid, $this->faker->uuid];
        $action = QueueActionFactory::create(userId: $userId);
        $queueActionRepository = $this->mock(QueueActionRepository::class);
        $accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $entityMapper = $this->mock(EntityMapper::class);

        $queueActionRepository->shouldReceive('getActions')
            ->once()
            ->with([$userId], 100, [200], [], $afterEventId, $excludeEventIds)
            ->andReturn([$action]);
        $entityMapper->shouldReceive('isPrimaryEntity')
            ->once()
            ->with($action->entity)
            ->andReturn(false);

        $useCase = new GetQueueActions($queueActionRepository, $accessValidatorRepository, $entityMapper);

        // When
        $result = $useCase(new UserAuth($userId), 100, [200], $afterEventId, $excludeEventIds);

        // Then
        $this->assertCount(1, $result);
    }

    function testInvokeWithoutEventFiltersKeepsLegacyArguments()
    {
        // Given
        $userId = $this->faker->uuid;
        $action = QueueActionFactory::create(userId: $userId);
        $queueActionRepository = $this->mock(QueueActionRepository::class);
        $accessValidatorRepository = $this->mock(EntityAccessValidatorRepository::class);
        $entityMapper = $this->mock(EntityMapper::class);

        $queueActionRepository->shouldReceive('getActions')
            ->once()
            ->with([$userId], null, [], [], null, [])
            ->andReturn([$action]);
        $entityMapper->shouldReceive('isPrimaryEntity')
            ->once()
            ->with($action->entity)
            ->andReturn(false);

        $useCase = new GetQueueActions($queueActionRepository, $accessValidatorRepository, $entityMapper);

        // When
        $result = $useCase(new UserAuth($userId));

        // Then
        $this->assertCount(1, $result);
    }
}