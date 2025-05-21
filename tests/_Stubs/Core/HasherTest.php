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

    function testHashWithDecimalValues()
    {
        $data = [
            "id" => "5e066af9-e341-45b3-ada5-3ad3e8bc64a7",
            "date" => 1747742400,
            "animal_type" => 1,
            "price_total" => 3270000.0,
            "price_currency" => "COP",
            "price_unit" => 3000.0,
            "notes" => null,
            "transfer_status" => 1,
            "farm_id" => "1f3c15ba-9984-4114-bb8f-d3d5ae9ffc03"];

        // When
        $hash = Hasher::hash($data);


        // Then
        $this->assertEquals("e7bbdd37338e8f0f6f4a64459d2183ac0edb2ce39d6e97aeb623e57aa81470ff", $hash);
    }

    function testHashWithDecimalValuesCase2()
    {
        $data = [
            "date" => 1747742400,
            "animal_type" => 1,
            "price_total" => 32700.05,
            "price_currency" => "COP",
            "price_unit" => 230.233,
            "float" => 230.23,
            "float2" => 932.20,
        ];
        // When
        $hash = Hasher::hash($data);

        // Then
        $this->assertEquals("40f1bde7a43cb2d76b4b9ad683b2398f72917e19643ec54a18b0248814c6adb2", $hash);
    }


}
