<?php
namespace  {
    final class CompiledContainerManualInlineTest implements \Arakne\Spinneret\Container\SpinneretContainerInterface
    {
        private array $instances = [];
        private array $aliases = array (
);
        private array $servicesByTag = array (
);
        private array $serviceIds = array (
  'ArrayObject' => 1,
  'Psr\\Container\\ContainerInterface' => 1,
  'Arakne\\Spinneret\\Container\\SpinneretContainerInterface' => 1,
);

        #[\Override]
        public function get(string $id): mixed
        {
            $id = $this->aliases[$id] ?? $id;
            
            if ($id === \Psr\Container\ContainerInterface::class || $id === \Arakne\Spinneret\Container\SpinneretContainerInterface::class) {
                return $this;
            }

            return $this->instances[$id] ?? $this->load($id);
        }

        #[\Override]
        public function has(string $id): bool
        {
            return isset($this->serviceIds[$id]) || isset($this->instances[$id]);
        }

        #[\Override]
        public function set(string $id, mixed $value): void
        {
            $this->instances[$id] = $value;
        }

        #[\Override]
        public function findByTag(string $tag): iterable
        {
            foreach ($this->servicesByTag[$tag] ?? [] as $id) {
                yield $this->get($id);
            }
        }

        private function getOrNull(string $id): mixed
        {
            $id = $this->aliases[$id] ?? $id;

            if ($id === \Psr\Container\ContainerInterface::class || $id === \Arakne\Spinneret\Container\SpinneretContainerInterface::class) {
                return $this;
            }

            try {
                return $this->instances[$id] ??= $this->load($id, true);
            } catch (\Throwable) {
                return null;
            }
        }

        private function load(string $id, bool $ignoreInvalid = false): mixed
        {
            return match ($id) {
                'ArrayObject' => $this->instances['ArrayObject'] = new \ArrayObject([new \Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass(), new \Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments('foo', 42), \Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory::create('test'), ], 0, 'ArrayIterator'),

                default => throw new \Arakne\Spinneret\Container\Exception\ServiceNotFoundException(sprintf('Service "%s" not found.', $id)),
            };   
        }
    }
}