<?php

namespace Tests\Unit\Core\Factory;


use AppTank\Horus\Core\Factory\EntityOperationFactory;
use Tests\TestCase;

class EntityOperationFactoryTest extends TestCase
{

    function testFilterAttributesReservedInInsert()
    {
        $data = [
            "id" => "43196d90-d8e1-4c24-bbac-76fdfe58a0eb",
            "name" => "HHP Animal COW 5539",
            "code" => "UUJYWNUMRG",
            "chip_code" => "cbfa2352-bff9-4db9-8049-1168e4ee682b596472"
        ];

        foreach (EntityOperationFactory::ATTRIBUTES_RESERVED as $reserved) {
            $data[$reserved] = "should be removed";
        }

        // When
        $operation = EntityOperationFactory::createEntityInsert($this->faker->uuid(), "entity", $data, new \DateTimeImmutable());

        // Then
        foreach (EntityOperationFactory::ATTRIBUTES_RESERVED as $reserved) {
            $this->assertArrayNotHasKey($reserved, $operation->data);
        }
        $this->assertArrayHasKey("id", $operation->data);
        $this->assertArrayHasKey("name", $operation->data);
        $this->assertArrayHasKey("code", $operation->data);
        $this->assertArrayHasKey("chip_code", $operation->data);
    }

    function testFilterAttributesReservedUpdate()
    {
        $attributes = [
            "name" => "HHP Animal COW 5539",
            "code" => "UUJYWNUMRG",
            "chip_code" => "cbfa2352-bff9-4db9-8049-1168e4ee682b596472"
        ];

        foreach (EntityOperationFactory::ATTRIBUTES_RESERVED as $reserved) {
            $attributes[$reserved] = "should be removed";
        }

        // When
        $operation = EntityOperationFactory::createEntityUpdate($this->faker->uuid(), "entity", $this->faker->uuid(), $attributes, new \DateTimeImmutable());

        // Then
        foreach (EntityOperationFactory::ATTRIBUTES_RESERVED as $reserved) {
            $this->assertArrayNotHasKey($reserved, $operation->attributes);
        }
        $this->assertArrayHasKey("name", $operation->attributes);
        $this->assertArrayHasKey("code", $operation->attributes);
        $this->assertArrayHasKey("chip_code", $operation->attributes);
    }
}
