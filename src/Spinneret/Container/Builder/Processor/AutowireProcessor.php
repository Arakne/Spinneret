<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Argument\Autowire;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Override;
use ReflectionClass;
use ReflectionNamedType;

use function count;

final readonly class AutowireProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $resolved = [];

        do {
            foreach ($builder->services as $id => $service) {
                if (isset($resolved[$id])) {
                    continue;
                }

                $resolved[$id] = true;
                $factory = $service->resolveFactory();

                if ($factory !== null) {
                    // Resolve parameters from the factory.
                    $parameters = $factory->parameters();

                    // The factory is called from a service which is not registered in the builder.
                    if (
                        $factory instanceof MethodServiceFactory
                        && $factory->object instanceof Reference
                        && !isset($builder->services[$factory->object->id])
                        && !isset($builder->aliases[$factory->object->id])
                    ) {
                        $builder->register($factory->object->id);
                    }
                } else {
                    // Resolve parameters from the constructor.
                    $parameters = new ReflectionClass($service->class)->getConstructor()?->getParameters();
                }

                // Cannot resolve parameter types, skip autowiring.
                if ($parameters === null || $parameters === []) {
                    continue;
                }

                // Mark all missing parameters as autowired.
                foreach ($parameters as $index => $parameter) {
                    if (!isset($service->arguments[$index])) {
                        /** @psalm-suppress PropertyTypeCoercion */
                        $service->arguments[$index] = new Autowire($id, $parameter->getName());
                    }
                }

                foreach ($service->arguments as $index => $argument) {
                    $parameter = $parameters[$index] ?? null;

                    if (!$argument instanceof Autowire || $parameter === null) {
                        continue;
                    }

                    $parameterType = $parameter->getType();

                    // Only atomic object type can be autowired.
                    if (!$parameterType instanceof ReflectionNamedType || $parameterType->isBuiltin()) {
                        continue;
                    }

                    $typeName = $parameterType->getName();

                    // Auto-register the service if not set.
                    if (!$builder->defined($typeName)) {
                        $builder->register($typeName);
                    }

                    // @todo handle nullable
                    /** @psalm-suppress PropertyTypeCoercion */
                    $service->arguments[$index] = new Reference($parameterType->getName());
                }
            }
        } while (count($resolved) < count($builder->services)); // Repeat until no new service is added
    }
}
