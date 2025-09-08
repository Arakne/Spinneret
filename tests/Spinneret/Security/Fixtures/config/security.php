<?php

use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUserHandler;

return new SecurityConfig(
    secret: 'my_secret',
    ttl: 3600,
    refreshThreshold: 300,
    extendExpiration: true,
    userHandler: TestUserHandler::class,
);
