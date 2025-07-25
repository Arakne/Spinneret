<?php

namespace Arakne\Spinneret\Container;

use Arakne\Spinneret\Container\Compiler\ContainerCompilerInterface;
use Arakne\Spinneret\Container\Compiler\PhpClassContainerCompiler;
use Arakne\Spinneret\Container\Exception\ServiceNotFoundException;
use Arakne\Spinneret\Container\Service\ServiceMetadata;
use Override;
use Psr\Container\ContainerInterface;

use function sprintf;

// @todo use custom ContainerInterface with custom methods
final class BuiltContainer implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
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
    public function get(string $id)
    {
        $id = $this->resolveAlias($id);

        return $this->instances[$id] ??= $this->instantiate($id);
    }

    #[Override]
    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->aliases[$id]);
    }

    /**
     * @param string $tag
     * @return iterable<mixed>
     *
     * @todo declare on interface instead
     */
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

    private function instantiate(string $id): mixed
    {
        $service = $this->services[$id] ?? throw new ServiceNotFoundException(sprintf('Service "%s" not found.', $id));
        $arguments = [];

        foreach ($service->arguments as $argument) {
            /** @var mixed */
            $arguments[] = $argument->resolve($this);
        }

        if ($service->factory !== null) {
            return $service->factory->create($this, $arguments);
        }

        /** @psalm-suppress MixedMethodCall */
        return new ($service->class)(...$arguments);
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
