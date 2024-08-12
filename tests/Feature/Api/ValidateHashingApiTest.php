<?php

namespace Api;

use AppTank\Horus\Core\Hasher;
use AppTank\Horus\RouteName;
use Tests\Feature\Api\ApiTestCase;

class ValidateHashingApiTest extends ApiTestCase
{

    function testValidateHashingIsSuccess()
    {
        $data = ["z1" => $this->faker->uuid, "age" => rand(1, 100), "mood" => $this->faker->uuid, "date" => now()->timestamp];

        $hashExpected = Hasher::hash($data);

        $input = [
            "data" => $data,
            "hash" => $hashExpected
        ];

        $response = $this->postJson(route(RouteName::POST_VALIDATE_HASHING->value), $input);

        $response->assertOk();
        $response->assertJson([
            "expected" => $hashExpected,
            "obtained" => $hashExpected,
            "matched" => true
        ]);
    }

    function testValidateHashingIsNotMatch()
    {
        $data = ["z1" => $this->faker->uuid, "age" => rand(1, 100), "mood" => $this->faker->uuid, "date" => now()->timestamp];

        $hashExpected = hash("sha1", json_encode($data));

        $input = [
            "data" => $data,
            "hash" => $hashExpected
        ];

        $response = $this->postJson(route(RouteName::POST_VALIDATE_HASHING->value), $input);

        $response->assertOk();
        $response->assertJson([
            "expected" => $hashExpected,
            "matched" => false
        ]);
    }
}