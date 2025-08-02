<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Value\Autowire;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Override;
use ReflectionNamedType;

use ReflectionParameter;

use function array_push;
use function count;

/**
 * Resolve values from missing arguments or with {@see Autowire} type.
 */
final readonly class AutowireProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $toProcess = $builder->services;

        while (count($toProcess) > 0) {
            $toProcess = $this->autowireServices($builder, $toProcess);
        }
    }

    /**
     * Try to autowire given services.
     *
     * @param ContainerBuilder $builder The container builder
     * @param ServiceBuilder[] $services Services to autowire
     *
     * @return list<ServiceBuilder> New register services, that should be processed again.
     */
    private function autowireServices(ContainerBuilder $builder, array $services): array
    {
        $toProcess = [];

        foreach ($services as $service) {
            if ($service->runtime) {
                continue;
            }

            $factory = $service->resolveFactory();

            if ($factory !== null) {
                // Resolve parameters from the factory.
                $parameters = $factory->parameters();

                // The factory is called from a service which is not registered in the builder.
                if (
                    $factory instanceof MethodServiceFactory
                    && $factory->object instanceof Reference
                    && !$builder->defined($factory->object->id)
                ) {
                    $toProcess[] = $builder->register($factory->object->id);
                }
            } else {
                // Resolve parameters from the constructor.
                $parameters = $service->reflection()?->getConstructor()?->getParameters();
            }

            // Cannot resolve parameter types, skip autowiring.
            if ($parameters === null || $parameters === []) {
                continue;
            }

            array_push($toProcess, ...$this->processArguments($builder, $service, $parameters));
        }

        return $toProcess;
    }

    /**
     * Process all {@see Autowire} arguments of the service and return new services to process.
     *
     * @param ContainerBuilder $builder
     * @param ServiceBuilder $service
     * @param ReflectionParameter[] $parameters
     *
     * @return list<ServiceBuilder>
     */
    private function processArguments(ContainerBuilder $builder, ServiceBuilder $service, array $parameters): array
    {
        $this->addMissingArguments($service, $parameters);

        $toProcess = [];

        foreach ($service->arguments as $index => $argument) {
            $parameter = $parameters[$index] ?? null;

            if (!$argument instanceof Autowire || $parameter === null) {
                continue;
            }

            $parameterType = $parameter->getType();

            // Only atomic object type can be autowired.
            if (!$parameterType instanceof ReflectionNamedType || $parameterType->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $service->arguments[$index] = new Literal($parameter->getDefaultValue());
                }

                continue;
            }

            $typeName = $parameterType->getName();

            // Auto-register the service if not set.
            if (!$builder->defined($typeName)) {
                $newService = $builder->register($typeName);
                $newService->ignoreIfInvalid = true;
                $toProcess[] = $newService;
            }

            /** @psalm-suppress PropertyTypeCoercion */
            $service->arguments[$index] = new Reference(
                $parameterType->getName(),
                $parameterType->allowsNull(),
                $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            );
        }

        return $toProcess;
    }

    /**
     * Push missing arguments to the service as {@see Autowire}.
     *
     * @param ServiceBuilder $service
     * @param ReflectionParameter[] $parameters
     */
    private function addMissingArguments(ServiceBuilder $service, array $parameters): void
    {
        foreach ($parameters as $index => $parameter) {
            if ($parameter->isVariadic()) {
                break;
            }

            if (!isset($service->arguments[$index])) {
                /** @psalm-suppress PropertyTypeCoercion */
                $service->arguments[$index] = new Autowire($service->id, $parameter->getName());
            }
        }
    }
}
