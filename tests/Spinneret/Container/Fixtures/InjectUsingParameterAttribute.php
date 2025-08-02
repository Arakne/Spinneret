<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

use Arakne\Spinneret\Container\Value\ClosureValue;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use Closure;

readonly class InjectUsingParameterAttribute
{
    public function __construct(
        #[Literal('Hello world!')]
        public string $value,

        #[PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'bar')]
        public int $number,

        #[ClosureValue(new Reference(SimpleClass::class))]
        public Closure $lazy,
    ) {}
}
