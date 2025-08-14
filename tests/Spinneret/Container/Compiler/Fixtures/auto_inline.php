<?php
namespace  {
    final class CompiledContainerAutoInlineTest implements \Arakne\Spinneret\Container\SpinneretContainerInterface
    {
        private array $instances = [];
        private array $aliases = array (
  'dispatcher' => 'Arakne\\Tests\\Spinneret\\Container\\Fixtures\\WithLoader\\MessageDispatcher',
);
        private array $servicesByTag = array (
);
        private array $serviceIds = array (
  'dispatcher' => 1,
  'Arakne\\Tests\\Spinneret\\Container\\Fixtures\\WithLoader\\MessageDispatcher' => 1,
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
                return $this->instances[$id] ?? $this->load($id, true);
            } catch (\Throwable) {
                return null;
            }
        }

        private function load(string $id, bool $ignoreInvalid = false): mixed
        {
            return match ($id) {
                'Arakne\\Tests\\Spinneret\\Container\\Fixtures\\WithLoader\\MessageDispatcher' => $this->instances['Arakne\\Tests\\Spinneret\\Container\\Fixtures\\WithLoader\\MessageDispatcher'] = new \Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\MessageDispatcher(['Arakne\\Tests\\Spinneret\\Container\\Fixtures\\WithLoader\\Messages\\DoA' => new \Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages\DoAHandler(), 'Arakne\\Tests\\Spinneret\\Container\\Fixtures\\WithLoader\\Messages\\DoB' => new \Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages\DoBHandler(new \Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\SimpleDep(new \Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\DepConfig('my-key'))), ]),

                default => $ignoreInvalid ? null : throw new \Arakne\Spinneret\Container\Exception\ServiceNotFoundException(sprintf('Service "%s" not found.', $id)),
            };   
        }
    }
}