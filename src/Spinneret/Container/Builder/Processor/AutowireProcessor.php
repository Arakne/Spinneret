<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Value\Autowire;
use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\NestedValueInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Override;
use ReflectionNamedType;
use ReflectionParameter;

use function array_push;
use function count;
use function is_array;

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

            array_push($toProcess, ...$this->processServiceArguments($builder, $service, $parameters ?? []));
        }

        return $toProcess;
    }

    /**
     * Process all {@see Autowire} arguments of the service and return new services to process.
     *
     * @param ContainerBuilder $builder
     * @param ServiceBuilder $service
     * @param list<ReflectionParameter> $parameters
     *
     * @return list<ServiceBuilder>
     */
    private function processServiceArguments(ContainerBuilder $builder, ServiceBuilder $service, array $parameters): array
    {
        [$arguments, $toProcess] = $this->processArguments($builder, $service->arguments, $parameters, $service->id);
        $service->arguments = $arguments;

        return $toProcess;
    }

    /**
     * @param ContainerBuilder $builder
     * @param list<mixed> $arguments Arguments to process.
     * @param list<ReflectionParameter> $parameters Constructor / factory parameters to use for autowiring.
     * @param string|null $serviceId The service ID, for debug purposes.
     *
     * @return list{list<mixed>, list<ServiceBuilder>} New arguments to use, and new services to process.
     */
    private function processArguments(ContainerBuilder $builder, array $arguments, array $parameters, ?string $serviceId = null): array
    {
        $arguments = $this->addMissingArguments($arguments, $parameters, $serviceId);
        $toProcess = [];

        $newArguments = [];

        /** @var mixed $argument */
        foreach ($arguments as $index => $argument) {
            $parameter = $parameters[$index] ?? null;

            if ($argument === null) {
                $newArguments[$index] = null;
                continue;
            }

            if ($argument instanceof Autowire && $parameter !== null) {
                [$argument, $dependencies] = $this->processAutowireArgument($builder, $parameter, $argument);
                array_push($toProcess, ...$dependencies);
            }

            if (is_array($argument)) {
                $argument = new DynamicArray($argument);
                $useArray = true; // Keep track that the original argument was an array.
            } else {
                $useArray = false;
            }

            if ($argument instanceof NestedValueInterface) {
                [$argument, $dependencies] = $this->processNestedValue($builder, $argument);
                array_push($toProcess, ...$dependencies);
            }

            if ($useArray && $argument instanceof DynamicArray) {
                // Convert back to a simple array if it was originally an array.
                $argument = $argument->values;
            }

            /** @var mixed */
            $newArguments[$index] = $argument;
        }

        /** @var list{list<mixed>, list<ServiceBuilder>} */
        return [$newArguments, $toProcess];
    }

    /**
     * Push missing arguments as {@see Autowire}.
     *
     * @param list<mixed> $arguments
     * @param ReflectionParameter[] $parameters
     * @param string|null $serviceId The service ID, for debug purposes.
     *
     * @return list<mixed> The arguments with missing ones added as {@see Autowire} instances.
     */
    private function addMissingArguments(array $arguments, array $parameters, ?string $serviceId = null): array
    {
        foreach ($parameters as $index => $parameter) {
            if ($parameter->isVariadic()) {
                break;
            }

            if (!isset($arguments[$index])) {
                $arguments[$index] = new Autowire($serviceId, $parameter->getName());
            }
        }

        /** @var list<mixed> */
        return $arguments;
    }

    /**
     * Process an argument marked as autowire.
     *
     * @param ContainerBuilder $builder
     * @param ReflectionParameter $parameter
     * @param Autowire $argument
     *
     * @return list{ValueInterface, list<ServiceBuilder>} New argument to set on the service, and new services to process.
     */
    private function processAutowireArgument(ContainerBuilder $builder, ReflectionParameter $parameter, Autowire $argument): array
    {
        if ($newArgument = $this->processParameterAttribute($parameter)) {
            return [$newArgument, []];
        }

        $parameterType = $parameter->getType();

        // Only atomic object type can be autowired.
        if (!$parameterType instanceof ReflectionNamedType || $parameterType->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return [new Literal($parameter->getDefaultValue()), []];
            }

            return [$argument, []];
        }

        $typeName = $parameterType->getName();
        $toProcess = [];

        // Auto-register the service if not set.
        if (!$builder->defined($typeName)) {
            $newService = $builder->register($typeName);
            $newService->ignoreIfInvalid = true;
            $toProcess[] = $newService;
        }

        $argument = new Reference(
            $parameterType->getName(),
            $parameterType->allowsNull(),
            $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
        );

        return [$argument, $toProcess];
    }

    /**
     * Process attributes of tye {@see ValueInterface} on the parameter.
     *
     * @param ReflectionParameter $parameter
     *
     * @return ValueInterface|null The value to set on the service argument, or null if no attribute was found.
     */
    private function processParameterAttribute(ReflectionParameter $parameter): ?ValueInterface
    {
        foreach ($parameter->getAttributes(ValueInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $ra) {
            return $ra->newInstance();
        }

        return null;
    }

    /**
     * @param ContainerBuilder $builder
     * @param NestedValueInterface $value
     *
     * @return list{ValueInterface, list<ServiceBuilder>} New value to set on the service, and new services to process.
     */
    private function processNestedValue(ContainerBuilder $builder, NestedValueInterface $value): array
    {
        $toProcess = [];
        $generator = $value->traverse();

        while ($generator->valid()) {
            $inner = $generator->current();
            $newValue = null;

            [$newValue, $dependencies] = match (true) {
                $inner instanceof Reference => $this->processReference($builder, $inner),
                $inner instanceof Call => $this->processCall($builder, $inner),
                $inner instanceof NestedValueInterface => $this->processNestedValue($builder, $inner),
                default => [null, []],
            };

            array_push($toProcess, ...$dependencies);

            if ($newValue === null || $newValue === $inner) {
                $generator->next();
            } else {
                $generator->send($newValue);
            }
        }

        return [$generator->getReturn(), $toProcess];
    }

    /**
     * Register a reference if it is not already defined in the container.
     *
     * @param ContainerBuilder $builder
     * @param Reference $reference
     *
     * @return list{ValueInterface, list<ServiceBuilder>} New reference value, and new services to process.
     */
    private function processReference(ContainerBuilder $builder, Reference $reference): array
    {
        if (!$builder->defined($reference->id)) {
            return [$reference, [$builder->register($reference->id)]];
        }

        return [$reference, []];
    }

    /**
     * Process autowiring on a call expression.
     *
     * @param ContainerBuilder $builder
     * @param Call $call
     *
     * @return list{Call, list<ServiceBuilder>} The processed call and new services to process.
     * @throws \ReflectionException
     */
    private function processCall(ContainerBuilder $builder, Call $call): array
    {
        $toProcess = [];
        $function = ServiceFactoryConverter::convert($call->function);

        if (
            $function instanceof MethodServiceFactory
            && $function->object instanceof Reference
            && !$builder->defined($function->object->id)
        ) {
            $toProcess[] = $builder->register($function->object->id);
        }

        $parameters = $function->parameters() ?? [];
        [$arguments, $dependencies] = $this->processArguments($builder, $call->arguments, $parameters);
        array_push($toProcess, ...$dependencies);

        if ($arguments !== $call->arguments) {
            $call = new Call($function, $arguments);
        }

        return [$call, $toProcess];
    }
}
