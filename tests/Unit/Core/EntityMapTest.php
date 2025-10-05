<?php

namespace Tests\Unit\Core;

use AppTank\Horus\Core\EntityMap;
use AppTank\Horus\Core\Mapper\EntityMapper;
use Tests\TestCase;

class EntityMapTest extends TestCase
{

    function testGenerateEntityMap()
    {

        $map = [
            // Migration order
            "farms" => [
                "measure_values",
                "farm_metadata",
                "farms_config",
                "animals" => [
                    "animal_tree",
                    "animal_favorites",
                    "animal_alerts" => [
                        "animal_alerts_metadata"
                    ],
                    "animal_inseminations" => [
                        "animal_insemination_metadata"
                    ],
                ],
                "farm_animal_purchase" => [
                    "animal_purchase_metadata",
                    "animal_purchase_animals"
                ],
                "farm_animal_sale" => [
                    "farm_animal_sale_metadata",
                    "farm_animal_sale_animals"
                ],
                "lots" => [
                    "animal_lots"
                ],
                "owners" => [
                    "animal_owners"
                ],
                "herds"
            ],
            "animal_breeds",
            "milk_buyers" => [
                "milk_buyer_defaults"
            ],
            "user_notifications" => [
                "user_notification_metadata",
            ],
            "user_notification_channels"
        ];

        $entityMapper = new EntityMapper();

        foreach ($this->createMap($map) as $entityMap) {
            $entityMapper->pushMap($entityMap);
        }

        // When
        $paths = $entityMapper->getPaths();

        // Then

        // Validate the array will be a bi-dimensional array
        $this->assertIsArray($paths);
        $this->assertCount($this->countAllValues($map), array_keys($paths));
        foreach ($paths as $path) {
            $this->assertIsArray($path);
            foreach ($path as $pathItem) {
                $this->assertIsNotArray($pathItem);
            }
        }
    }

    private function createMap(array $entitiesMap): array
    {
        $output = [];
        foreach ($entitiesMap as $entity => $entities) {
            if (is_array($entities)) {
                $output[] = new EntityMap($entity, $this->createMap($entities));
                continue;
            }
            $output[] = new EntityMap($entities);
        }
        return $output;
    }

    private function countAllValues(array $array): int
    {
        $count = 0;

        array_walk_recursive($array, function () use (&$count) {
            $count++;
        });

        return $count;
    }
}
