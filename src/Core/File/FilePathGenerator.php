<?php

namespace AppTank\Horus\Core\File;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Repository\EntityRepository;

/**
 * @internal Class FilePathGenerator
 *
 * Represents a class for generating file paths based on user authentication and entity reference.
 *
 * @package AppTank\Horus\Core\File
 *
 * @author John Ospina
 * Year: 2024
 */
readonly class FilePathGenerator
{
    /**
     * Constructor of the `FilePathGenerator` class.
     *
     * @param EntityRepository $entityRepository Repository for entity operations.
     * @param Config $config Configuration for the file path generator.
     */
    function __construct(
        private EntityRepository $entityRepository,
        private Config           $config
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
    function createPathEntityReference(UserAuth        $userAuth,
                                       EntityReference $entityReference): string
    {

        $path = "{$this->config->basePathFiles}/{$userAuth->getEffectiveUserId()}/";
        $entityHierarchy = $this->entityRepository->getEntityPathHierarchy($entityReference);

        foreach ($entityHierarchy as $entity) {
            $path .= "{$entity->entityName}/{$entity->getId()}/";
        }

        return $path;
    }


    function createCustomPath(string $path): string
    {
        return "{$this->config->basePathFiles}/{$path}/";
    }
}