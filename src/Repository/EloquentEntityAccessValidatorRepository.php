<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Auth\EntityGranted;
use AppTank\Horus\Core\Auth\Permission;
use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Mapper\EntityMapper;
use AppTank\Horus\Core\Repository\EntityAccessValidatorRepository;
use AppTank\Horus\Illuminate\Database\EntityDependsOn;
use AppTank\Horus\Illuminate\Database\EntitySynchronizable;

/**
 * Clase EloquentEntityAccessValidatorRepository
 *
 * Implementación del repositorio de validación de acceso a entidades.
 * Proporciona mecanismos para validar si un usuario tiene acceso a una entidad específica
 * basándose en la propiedad, los permisos otorgados, y las relaciones de jerarquía entre entidades.
 *
 * @author John Ospina
 * @year 2024
 */
readonly class EloquentEntityAccessValidatorRepository implements EntityAccessValidatorRepository
{
    /**
     * Constructor de la clase `EloquentEntityAccessValidatorRepository`.
     *
     * @param EntityMapper $entityMapper Mapeador de entidades utilizado para obtener clases de entidad y jerarquías.
     * @param Config $config Configuración de la aplicación.
     */
    public function __construct(
        private EntityMapper $entityMapper,
        private Config       $config
    )
    {
    }

    /**
     * Valida si un usuario tiene acceso a una entidad específica con un permiso determinado.
     *
     * @param UserAuth $userAuth Autenticación del usuario que solicita el acceso.
     * @param EntityReference $entityReference Referencia a la entidad que se desea acceder.
     * @param Permission $permission Permiso que se quiere validar.
     * @return bool Retorna `true` si el usuario tiene acceso, de lo contrario `false`.
     */
    public function canAccessEntity(UserAuth        $userAuth,
                                    EntityReference $entityReference,
                                    Permission      $permission): bool
    {
        // 1. Validate if access validation is disabled
        if (!$this->config->validateAccess) {
            return true;
        }

        // 2. Validate if user is owner
        if ($this->isEntityOwner($entityReference->entityName, $entityReference->entityId, $userAuth->userId)) {
            return true;
        }

        // 3. Validate if user has granted permission
        if ($userAuth->hasGranted($entityReference->entityName, $entityReference->entityId, $permission)) {
            return true;
        }

        // 4. Validate if user has access on cascade by entity granted
        if ($this->canAccessOnCascade($userAuth, $entityReference, $permission)) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene acceso a la entidad a través de una jerarquía de entidades.
     *
     * @param UserAuth $userAuth Autenticación del usuario.
     * @param EntityReference $entityReference Referencia a la entidad que se desea acceder.
     * @param Permission $permissionRequested Permiso que se quiere validar.
     * @return bool Retorna `true` si el usuario tiene acceso por jerarquía, de lo contrario `false`.
     */
    private function canAccessOnCascade(UserAuth $userAuth, EntityReference $entityReference, Permission $permissionRequested): bool
    {
        if (empty($userAuth->entityGrants)) {
            return false;
        }

        if (!$this->canAccessByParentHierarchyWithPath($userAuth, $entityReference)) {
            return false;
        }

        $entityHierarchy = $this->buildPathHierarchy($entityReference);

        foreach ($userAuth->entityGrants as $entityGrant) {
            if ($this->entityGrantedIsInEntityHierarchy($entityGrant, $entityHierarchy) &&
                $entityGrant->accessLevel->can($permissionRequested)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene acceso a la entidad a través de la jerarquía de entidades padres.
     *
     * @param UserAuth $userAuth Autenticación del usuario.
     * @param EntityReference $entityReference Referencia a la entidad que se desea acceder.
     * @return bool Retorna `true` si el usuario tiene acceso por jerarquía de padres, de lo contrario `false`.
     */
    private function canAccessByParentHierarchyWithPath(UserAuth $userAuth, EntityReference $entityReference): bool
    {
        $paths = $this->entityMapper->getPaths();

        foreach ($paths as $path) {
            foreach ($userAuth->entityGrants as $entityGrant) {
                $parentIndexPath = array_search($entityGrant->entityReference->entityName, $path);
                $childIndexPath = array_search($entityReference->entityName, $path);

                // Validate if entity granted is parent of entity reference to access
                if (in_array($entityGrant->entityReference->entityName, $path) &&
                    in_array($entityReference->entityName, $path) &&
                    $parentIndexPath < $childIndexPath) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Construye una jerarquía de rutas basada en la entidad referenciada, considerando su relación con entidades padres.
     *
     * @param EntityReference $entityRefChild Referencia a la entidad hijo para construir la jerarquía.
     * @return array Retorna un array con la jerarquía de entidades.
     */
    private function buildPathHierarchy(EntityReference $entityRefChild): array
    {
        $entityHierarchy = [];

        /**
         * @var EntitySynchronizable $entityClass
         */
        $entityClass = $this->getEntityClass($entityRefChild->entityName);
        $entity = $entityClass::query()->where(EntitySynchronizable::ATTR_ID, $entityRefChild->entityId)->first();

        if ($entity instanceof EntityDependsOn) {
            /**
             * @var EntitySynchronizable $entityParent
             */
            $entityParent = $entity->dependsOn();

            $entityHierarchy = array_merge($entityHierarchy,
                $this->buildPathHierarchy(
                    new EntityReference($entityParent::class::getEntityName(), $entityParent->getId())
                )
            );
        }

        $entityHierarchy[] = $entity;

        return $entityHierarchy;
    }

    /**
     * Verifica si la entidad concedida está presente en la jerarquía de entidades.
     *
     * @param EntityGranted $entityGranted Entidad que ha sido concedida.
     * @param array $entityHierarchy Jerarquía de entidades construida.
     * @return bool Retorna `true` si la entidad concedida se encuentra en la jerarquía, de lo contrario `false`.
     */
    private function entityGrantedIsInEntityHierarchy(EntityGranted $entityGranted, array $entityHierarchy): bool
    {
        foreach ($entityHierarchy as $entity) {
            if ($entity->entityName == $entityGranted->entityReference->entityName &&
                $entity->getId() == $entityGranted->entityReference->entityId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si un usuario es propietario de una entidad específica.
     *
     * @param string $entityName Nombre de la entidad.
     * @param string $entityId ID de la entidad.
     * @param string|int $userId ID del usuario.
     * @return bool Retorna `true` si el usuario es el propietario de la entidad, de lo contrario `false`.
     */
    private function isEntityOwner(string $entityName, string $entityId, string|int $userId): bool
    {
        /**
         * @var EntitySynchronizable $entityClass
         */
        $entityClass = $this->getEntityClass($entityName);

        return $entityClass::isOwner($entityId, $userId);
    }

    /**
     * Obtiene la clase de la entidad correspondiente a un nombre de entidad.
     *
     * @param string $entityName Nombre de la entidad.
     * @return string Nombre de la clase de la entidad.
     */
    private function getEntityClass(string $entityName): string
    {
        return $this->entityMapper->getEntityClass($entityName);
    }
}