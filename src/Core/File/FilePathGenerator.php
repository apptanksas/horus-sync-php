<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class FilePathGenerator
{
    /**
     * Constructor of the `FilePathGenerator` class.
     *
     * @param EntityRepository $entityRepository Repository for entity operations.
     */
    function __construct(
        private EntityRepository $entityRepository,
        private string           $basePath = "upload"
    )
    {

    }

    /**
     * Creates a path for a file upload based on the user authentication and entity reference.
     *
     * @param UserAuth $userAuth The user authentication data.
     * @param EntityReference $entityReference The entity reference data.
     *
     * @return string The path for the file upload.
     */
    function create(UserAuth        $userAuth,
                    EntityReference $entityReference): string
    {
        $path = "{$this->basePath}/{$userAuth->getEffectiveUserId()}/";
        $entityHierarchy = $this->entityRepository->getEntityPathHierarchy($entityReference);

        foreach ($entityHierarchy as $entity) {
            $path .= "{$entity->entityName}/{$entity->getId()}/";
        }

        return $path;
    }
}