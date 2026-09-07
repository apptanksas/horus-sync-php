<?php

use Illuminate\Http\Request;
use Tests\Feature\Api\ApiTestCase;

class BroadcastMiddlewaresTest extends ApiTestCase
{
    protected array $middlewares = [
        BroadcastFakeMiddleware::class,
    ];

    function testCustomMiddlewareIsAppliedToBroadcastAuthenticationRoute(): void
    {
        $response = $this->post('/horus/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-horus.sync.owner-id',
        ]);

        $response->assertStatus(101);
    }
}

class BroadcastFakeMiddleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        abort(101);
    }
}