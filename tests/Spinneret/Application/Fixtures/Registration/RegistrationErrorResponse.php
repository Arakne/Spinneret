<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

final readonly class RegistrationErrorResponse
{
    public function __construct(public array $errors)
    {
    }
}
