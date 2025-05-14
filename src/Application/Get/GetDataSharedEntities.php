<?php

namespace AppTank\Horus\Application\Get;

use AppTank\Horus\Core\Auth\UserAuth;
use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Entity\EntityReference;
use AppTank\Horus\Core\Model\EntityData;
use AppTank\Horus\Core\Repository\CacheRepository;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class GetDataSharedEntities extends BaseGetEntities
{
    const int CACHE_TTL_ONE_WEEK = 60 * 60 * 24 * 7; // 7 days
    const int CACHE_TTL_ONE_MONTH = 60 * 60 * 24 * 30; // 30 days


    function __construct(private EntityRepository $entityRepository,
                         private CacheRepository  $cacheRepository,
                         private Config           $config
    )
    {

    }

    /**
     * @param UserAuth $userAuth
     * @return array
     */
    function __invoke(UserAuth $userAuth): array
    {
        $key = md5($userAuth->getEffectiveUserId() . serialize($this->config->getSharedEntities()));

        return $this->cacheRepository->remember($key,self::CACHE_TTL_ONE_MONTH,fn()=>$this->getEntitiesData());
    }

    private function getEntitiesData(): array
    {
        $sharedEntitiesReferences = $this->config->getSharedEntities();
        $sharedEntitiesReferencesFiltered = [];

        /**
         * @var $entitiesData EntityData[]
         */
        $entitiesData = [];

        foreach ($sharedEntitiesReferences as $entityReference) {

            $cacheKey = $this->createCacheKey($entityReference);

            if ($this->cacheRepository->exists($cacheKey)) {
                $entitiesData[$entityReference->entityId] = $this->cacheRepository->get($cacheKey);
                continue;
            }

            $sharedEntitiesReferencesFiltered[] = $entityReference;
        }

        $result = $this->entityRepository->searchRawEntitiesByReference(...$sharedEntitiesReferencesFiltered);

        foreach ($result as $entityData) {
            $entityReference = new EntityReference($entityData->name, $entityData->getEntityId());
            $cacheKey = $this->createCacheKey($entityReference);

            $this->cacheRepository->set($cacheKey, $entityData, self::CACHE_TTL_ONE_WEEK);
            $entitiesData[$entityReference->entityId] = $entityData;
        }

        return $this->parseData($entitiesData);
    }

    /**
     * @param EntityReference $entityReference
     * @return string
     */
    private function createCacheKey(EntityReference $entityReference): string
    {
        return "shared_entity_{$entityReference->entityId}";
    }
}