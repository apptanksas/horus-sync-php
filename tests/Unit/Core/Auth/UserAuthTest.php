<?php

namespace Tests\Unit\Core\Auth;

use AppTank\Horus\Core\Auth\UserActingAs;
use AppTank\Horus\Core\Auth\UserAuth;
use PHPUnit\Framework\TestCase;

class UserAuthTest extends TestCase
{
    function testAuthIdentifierUsesEffectiveUserId(): void
    {
        $userAuth = new UserAuth('authenticated-user', userActingAs: new UserActingAs('acting-as-user'));

        $this->assertSame('acting-as-user', $userAuth->getAuthIdentifier());
    }
}