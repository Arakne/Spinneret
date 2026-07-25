<?php

namespace Arakne\Spinneret\Container;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Compiler\ContainerCompilerInterface;
use Arakne\Spinneret\Container\Compiler\PhpClassContainerCompiler;
use Arakne\Spinneret\Container\Exception\ServiceNotFoundException;
use Arakne\Spinneret\Container\Service\ServiceMetadata;
use Override;
use Psr\Container\ContainerInterface;

use function assert;
use function sprintf;

/**
 * Container built by {@see ContainerBuilder::build()}.
 * Service definitions and aliases cannot be modified on this container.
 */
final class BuiltContainer implements SpinneretContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Map of tags to service IDs.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $servicesByTag = null;

    public function __construct(
        /** @var array<string, ServiceMetadata> */
        public readonly array $services,

        /** @var array<string, string> */
        public readonly array $aliases = [],
    ) {}

    #[Override]
    public function get(string $id): mixed
    {
        $id = $this->resolveAlias($id);

        if ($id === ContainerInterface::class || $id === SpinneretContainerInterface::class) {
            return $this;
        }

        /** @psalm-suppress MixedReturnStatement */
        return $this->instances[$id] ?? $this->load($id);
    }

    #[Override]
    public function has(string $id): bool
    {
        return $id === ContainerInterface::class
            || $id === SpinneretContainerInterface::class
            || isset($this->instances[$id])
            || isset($this->services[$id])
            || isset($this->aliases[$id])
        ;
    }

    #[Override]
    public function set(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    #[Override]
    public function findByTag(string $tag): iterable
    {
        $servicesByTag = $this->servicesByTag ??= $this->loadTags();

        foreach ($servicesByTag[$tag] ?? [] as $id) {
            yield $this->get($id);
        }
    }

    /**
     * @param ContainerCompilerInterface<R> $compiler
     * @return R
     *
     * @template R as mixed
     */
    public function compile(ContainerCompilerInterface $compiler = new PhpClassContainerCompiler()): mixed
    {
        return $compiler->compile($this);
    }

    private function resolveAlias(string $id): string
    {
        while ($next = $this->aliases[$id] ?? null) {
            $id = $next;
        }

        return $id;
    }

    private function load(string $id): mixed
    {
        $service = $this->services[$id] ?? throw new ServiceNotFoundException(sprintf('Service "%s" not found.', $id));
        $arguments = [];

        foreach ($service->arguments as $argument) {
            $arguments[] = $argument->resolve($this);
        }

        if ($service->value !== null) {
            /** @var mixed $instance */
            $instance = $service->value->resolve($this);
        } elseif ($service->factory !== null) {
            /** @var mixed $instance */
            $instance = $service->factory->create($this, $arguments);
        } else {
            assert($service->class !== null);

            /** @psalm-suppress MixedMethodCall */
            $instance = new ($service->class)(...$arguments);
        }

        if ($service->shared) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * @return array<string, list<string>>
     */
    private function loadTags(): array
    {
        $servicesByTag = [];

        foreach ($this->services as $id => $service) {
            foreach ($service->tags as $tag) {
                $servicesByTag[$tag][] = $id;
            }
        }

        return $servicesByTag;
    }
}
