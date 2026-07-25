<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Value\Literal;
use Closure;
use ReflectionFunction;

use function function_exists;
use function is_string;
use function method_exists;

final class ServiceFactoryConverter
{
    /**
     * Convert a factory to a ServiceFactoryInterface instance.
     *
     * @param ServiceFactoryInterface|callable-string|Closure $factory
     *
     * @return ServiceFactoryInterface
     * @throws \ReflectionException
     */
    public static function convert(ServiceFactoryInterface|string|Closure $factory): ServiceFactoryInterface
    {
        if ($factory instanceof ServiceFactoryInterface) {
            return $factory;
        }

        if (is_string($factory)) {
            return new FunctionServiceFactory($factory);
        }

        $reflection = new ReflectionFunction($factory);
        $calledClass = $reflection->getClosureCalledClass()?->getName();
        $methodName = $reflection->getName();

        if ($calledClass !== null && method_exists($calledClass, $methodName)) {
            return $reflection->isStatic()
                ? new StaticMethodServiceFactory($calledClass, $reflection->getName())
                : new MethodServiceFactory(new Literal($reflection->getClosureThis()), $reflection->getName())
            ;
        }

        if ($calledClass === null && $reflection->isClosure() && function_exists($reflection->getName())) {
            return new FunctionServiceFactory($reflection->getName());
        }

        return new FunctionServiceFactory($factory);
    }
}
