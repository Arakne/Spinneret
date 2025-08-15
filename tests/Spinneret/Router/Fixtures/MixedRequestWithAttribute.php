<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Attribute\Route;

#[Route('/mixed-request-with-attribute', methods: ['GET', 'POST', 'PUT'])]
class MixedRequestWithAttribute
{

}
