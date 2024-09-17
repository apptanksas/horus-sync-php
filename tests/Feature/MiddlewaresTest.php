<?php

use AppTank\Horus\Horus;
use AppTank\Horus\RouteName;
use Illuminate\Http\Request;
use Tests\_Stubs\ParentFakeWritableEntity;
use Tests\Feature\Api\ApiTestCase;

class MiddlewaresTest extends ApiTestCase
{

    protected array $middlewares = [
        FakeMiddleware::class
    ];

    function testFakeMiddleware()
    {
        $userId = $this->faker->uuid;
        Horus::getInstance()->setUserAuthenticated(new \AppTank\Horus\Core\Auth\UserAuth($userId));

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ParentFakeWritableEntity::getEntityName()));

        // Then
        $response->assertStatus(101);
    }
}

class FakeMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        abort(101);
    }
}