<?php

namespace Tests\_Stubs\Core;

use AppTank\Horus\Core\Hasher;
use PHPUnit\Framework\TestCase;

class HasherTest extends TestCase
{

    function testWithNullsAndBooleansWithTrue()
    {
        $data = [
            "id" => "43196d90-d8e1-4c24-bbac-76fdfe58a0eb",
            "name" => "HHP Animal COW 5539",
            "code" => "UUJYWNUMRG",
            "chip_code" => "cbfa2352-bff9-4db9-8049-1168e4ee682b596472",
            "gender" => "f",
            "type" => 1,
            "purpose" => 2,
            "branding_iron_id" => null,
            "sale_status" => 1,
            "stage" => 4,
            "reproductive_status" => 1,
            "health_status" => 1,
            "inside" => true,
            "notes" => "5049c394-9445-45c9-9586-49d3690dabea434055",
            "farm_id" => "4e1b860c-22dc-477a-a86e-69dde6071874",
            "breed_code" => null
        ];

        // When
        $hash = Hasher::hash($data);

        // Then
        $this->assertEquals("4f707d4007e2ca5cb074f9c1b45b54b79dda8ad75419e50cfcd6a2d661c70b08", $hash);
    }

    function testWithNullsAndBooleansWithFalse()
    {
        $data = [
            "id" => "43196d90-d8e1-4c24-bbac-76fdfe58a0eb",
            "name" => "HHP Animal COW 5539",
            "code" => "UUJYWNUMRG",
            "chip_code" => "cbfa2352-bff9-4db9-8049-1168e4ee682b596472",
            "gender" => "f",
            "type" => 1,
            "purpose" => 2,
            "branding_iron_id" => null,
            "sale_status" => 1,
            "stage" => 4,
            "reproductive_status" => 1,
            "health_status" => 1,
            "inside" => false,
            "notes" => "5049c394-9445-45c9-9586-49d3690dabea434055",
            "farm_id" => "4e1b860c-22dc-477a-a86e-69dde6071874",
            "breed_code" => null
        ];

        // When
        $hash = Hasher::hash($data);

        // Then
        $this->assertEquals("3ed2ff2ed137d040c1400bc60b175c7ad0af2a5d3bdd2ac9ab2e995de723df00", $hash);
    }
}
