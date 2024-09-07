<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\ShowUser;

use Arakne\Spinneret\Router\Field\RequestAttribute;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;

class ShowUserRequest
{
    #[RequestAttribute]
    public TestUser $user;
}
