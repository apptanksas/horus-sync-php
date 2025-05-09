<?php

namespace AppTank\Horus\Core\Validator;

use AppTank\Horus\Core\Config\Config;
use AppTank\Horus\Core\Config\Restriction\MaxCountEntityRestriction;
use AppTank\Horus\Core\Exception\ClientException;
use AppTank\Horus\Core\Exception\OperationNotPermittedException;
use AppTank\Horus\Core\Exception\RestrictionException;
use AppTank\Horus\Core\Model\EntityInsert;
use AppTank\Horus\Core\Model\EntityOperation;
use AppTank\Horus\Core\Repository\EntityRepository;

readonly class EntityRestrictionValidator
{
    public function __construct(
        private EntityRepository $entityRepository,
        private Config           $config,
    )
    {
    }


    /**
     * Validates the insert entity restrictions.
     *
     * @param EntityOperation[] $insertOperations
     * @return void
     * @throws ClientException
     */

    function validateInsertEntityRestrictions(string|int $userOwnerId, array $insertOperations): void
    {
        $insertGrouped = array_reduce($insertOperations, function ($carry, EntityInsert $operation) {
            $carry[$operation->entity] = $carry[$operation->entity] ?? 0;
            $carry[$operation->entity] += 1;
            return $carry;
        }, []);


        foreach ($insertGrouped as $entity => $countToInsert) {

            // Check if entity has restrictions
            if (!$this->config->hasRestrictions($entity)) {
                continue;
            }

            $restrictions = $this->config->getRestrictionsByEntity($entity);

            foreach ($restrictions as $restriction) {
                // Validate max count entity restriction
                if ($restriction instanceof MaxCountEntityRestriction) {
                    $this->validateMaxCountEntityRestriction($userOwnerId, $restriction, $entity, $countToInsert);
                    continue;
                }

                throw new OperationNotPermittedException("Restriction not supported");
            }
        }
    }


    /**
     * Validates the max count entity restriction.
     *
     * @param string|int $userOwnerId
     * @param MaxCountEntityRestriction $restriction
     * @param string $entity
     * @param int $countToInsert
     * @return void
     * @throws ClientException
     */
    private function validateMaxCountEntityRestriction(
        string|int                $userOwnerId,
        MaxCountEntityRestriction $restriction,
        string                    $entity,
        int                       $countToInsert): void
    {
        $currentCount = $this->entityRepository->getCount($userOwnerId, $entity);

        if (($currentCount + $countToInsert) > $restriction->maxCount) {
            throw new RestrictionException("Max count entity [$entity] restriction exceeded");
        }
    }
}