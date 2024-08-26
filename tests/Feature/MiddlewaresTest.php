<?php

use AppTank\Horus\HorusContainer;
use AppTank\Horus\RouteName;
use Illuminate\Http\Request;
use Tests\_Stubs\ParentFakeEntity;
use Tests\Feature\Api\ApiTestCase;

class MiddlewaresTest extends ApiTestCase
{

    protected array $middlewares = [
        FakeMiddleware::class
    ];

    function testFakeMiddleware()
    {
        $userId = $this->faker->uuid;
        HorusContainer::getInstance()->setAuthenticatedUserId($userId);

        // When
        $response = $this->get(route(RouteName::GET_ENTITY_DATA->value, ParentFakeEntity::getEntityName()));

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