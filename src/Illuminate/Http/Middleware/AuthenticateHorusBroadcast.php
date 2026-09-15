<?php

namespace AppTank\Horus\Illuminate\Http\Middleware;

use AppTank\Horus\Horus;
use Closure;
use Illuminate\Http\Request;

class AuthenticateHorusBroadcast
{
    public function handle(Request $request, Closure $next): mixed
    {
        $userAuth = Horus::getInstance()->getUserAuthenticated();
        $request->setUserResolver(fn() => $userAuth);

        return $next($request);
    }
}