<?php

use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUserHandler;

return new SecurityConfig(
    secret: 'my_secret',
    userHandler: TestUserHandler::class,
);
