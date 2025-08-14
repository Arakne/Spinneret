<?php

namespace Arakne\Spinneret\Container\Compiler;

use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\BuiltContainer;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\ServiceMetadata;
use Arakne\Spinneret\Container\SpinneretContainerInterface;
use Override;
use Psr\Container\ContainerInterface;
use Throwable;

use function assert;
use function implode;
use function sprintf;
use function var_export;

/**
 * Compile the container into a PHP class that implements `Psr\Container\ContainerInterface`.
 *
 * @implements ContainerCompilerInterface<string>
 */
final readonly class PhpClassContainerCompiler implements ContainerCompilerInterface
{
    public function __construct(
        public string $className = 'CompiledContainer',
        public string $namespace = '',
    ) {}

    #[Override]
    public function compile(BuiltContainer $container): string
    {
        return <<<PHP
            namespace {$this->namespace} {
                final class {$this->className} implements \Arakne\Spinneret\Container\SpinneretContainerInterface
                {
                    private array \$instances = [];
                    private array \$aliases = {$this->buildAliases($container)};
                    private array \$servicesByTag = {$this->buildTags($container)};
                    private array \$serviceIds = {$this->buildServiceIds($container)};
            
                    #[\Override]
                    public function get(string \$id): mixed
                    {
                        \$id = \$this->aliases[\$id] ?? \$id;
                        
                        if (\$id === \Psr\Container\ContainerInterface::class || \$id === \Arakne\Spinneret\Container\SpinneretContainerInterface::class) {
                            return \$this;
                        }
            
                        return \$this->instances[\$id] ?? \$this->load(\$id);
                    }
            
                    #[\Override]
                    public function has(string \$id): bool
                    {
                        return isset(\$this->serviceIds[\$id]) || isset(\$this->instances[\$id]);
                    }
            
                    #[\Override]
                    public function set(string \$id, mixed \$value): void
                    {
                        \$this->instances[\$id] = \$value;
                    }
            
                    #[\Override]
                    public function findByTag(string \$tag): iterable
                    {
                        foreach (\$this->servicesByTag[\$tag] ?? [] as \$id) {
                            yield \$this->get(\$id);
                        }
                    }
            
                    private function getOrNull(string \$id): mixed
                    {
                        \$id = \$this->aliases[\$id] ?? \$id;
            
                        if (\$id === \Psr\Container\ContainerInterface::class || \$id === \Arakne\Spinneret\Container\SpinneretContainerInterface::class) {
                            return \$this;
                        }
            
                        try {
                            return \$this->instances[\$id] ?? \$this->load(\$id, true);
                        } catch (\Throwable) {
                            return null;
                        }
                    }
            
                    private function load(string \$id, bool \$ignoreInvalid = false): mixed
                    {
                        return match (\$id) {
                            {$this->buildServiceInstantiations($container)}
                            default => \$ignoreInvalid ? null : throw new \Arakne\Spinneret\Container\Exception\ServiceNotFoundException(sprintf('Service "%s" not found.', \$id)),
                        };   
                    }
                }
            }
            PHP;
    }

    private function buildAliases(BuiltContainer $container): string
    {
        $aliases = [];

        foreach ($container->aliases as $alias => $id) {
            while ($next = $container->aliases[$id] ?? null) {
                $id = $next;
            }

            $aliases[$alias] = $id;
        }

        return var_export($aliases, true);
    }

    private function buildTags(BuiltContainer $container): string
    {
        $servicesByTag = [];

        foreach ($container->services as $id => $service) {
            foreach ($service->tags as $tag) {
                $servicesByTag[$tag][] = $id;
            }
        }

        return var_export($servicesByTag, true);
    }

    private function buildServiceIds(BuiltContainer $container): string
    {
        $ids = [];

        foreach ($container->aliases as $alias => $_) {
            $ids[$alias] = 1;
        }

        foreach ($container->services as $id => $_) {
            $ids[$id] = 1;
        }

        $ids[ContainerInterface::class] = 1;
        $ids[SpinneretContainerInterface::class] = 1;

        return var_export($ids, true);
    }

    private function buildServiceInstantiations(BuiltContainer $container): string
    {
        $cases = '';

        foreach ($container->services as $id => $service) {
            try {
                $cases .= sprintf(
                    "%s => %s,\n",
                    var_export($id, true),
                    $this->buildServiceInstantiation($id, $service)
                );
            } catch (Throwable $e) {
                if ($service->ignoreIfInvalid) {
                    continue;
                }

                throw new ContainerBuildException(
                    sprintf('Failed to compile service "%s": %s', $id, $e->getMessage()),
                    previous: $e
                );
            }
        }

        return $cases;
    }

    private function buildServiceInstantiation(string $id, ServiceMetadata $service): string
    {
        $arguments = $this->buildArguments($service->arguments);
        $factory = $service->factory;

        if ($factory === null) {
            assert($service->class !== null);
            $instantiation = sprintf('new \%s(%s)', $service->class, $arguments);
        } else {
            $instantiation = $factory->compile($arguments);
        }

        if ($service->shared) {
            $instantiation = sprintf('$this->instances[%s] = %s', var_export($id, true), $instantiation);
        }

        return $instantiation;
    }

    private function buildArguments(array $arguments): string
    {
        return implode(', ', array_map($this->buildArgument(...), $arguments));
    }

    private function buildArgument(ValueInterface $argument): string
    {
        return $argument->compile();
    }
}
