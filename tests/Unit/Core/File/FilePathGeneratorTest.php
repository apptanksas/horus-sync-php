<?php

namespace Tests\Unit\Core\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\File\FilePathGenerator;
use AppTank\Horus\Horus;
use AppTank\Horus\Illuminate\Util\DateTimeUtil;
use AppTank\Horus\Repository\EloquentEntityRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\_Stubs\ChildFakeEntityFactory;
use Tests\_Stubs\ChildFakeWritableEntity;
use Tests\_Stubs\ParentFakeEntityFactory;
use Tests\TestCase;

class FilePathGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private FilePathGenerator $filePathGenerator;

    public function setUp(): void
    {
        parent::setUp();
        $mapper = Horus::getInstance()->getEntityMapper();

        $repository = new EloquentEntityRepository($mapper, new DateTimeUtil());

        $this->filePathGenerator = new FilePathGenerator($repository);
    }

    function testCreate()
    {
        // Given
        $userOwnerId = $this->faker->uuid;

        $parentEntity = ParentFakeEntityFactory::create($userOwnerId);
        $childEntity = ChildFakeEntityFactory::create($parentEntity->getId(), $userOwnerId);
        $entityReference = new EntityReference(ChildFakeWritableEntity::getEntityName(), $childEntity->getId());

        $pathExpected = "upload/{$userOwnerId}/{$parentEntity->entityName}/{$parentEntity->getId()}/{$childEntity->entityName}/{$childEntity->getId()}/";

        // When
        $path = $this->filePathGenerator->create(new UserAuth($userOwnerId), $entityReference);

        // Then
        $this->assertEquals($pathExpected, $path);
    }
}
