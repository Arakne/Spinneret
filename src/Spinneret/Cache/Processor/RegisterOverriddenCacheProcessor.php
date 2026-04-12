<?php

namespace Arakne\Spinneret\Cache\Processor;

use Arakne\Spinneret\Cache\CacheConfig;
use Arakne\Spinneret\Cache\CacheFetcher;
use Arakne\Spinneret\Cache\Driver\CacheDriverInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Container\Value\Reference;
use Override;
use Psr\Container\ContainerInterface;

use Psr\SimpleCache\CacheInterface;

use function class_exists;
use function in_array;
use function interface_exists;
use function is_subclass_of;

final readonly class RegisterOverriddenCacheProcessor implements ContainerBuilderProcessorInterface
{
    public function __construct(
        private CacheConfig $config,
    ) {}

    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        foreach ($this->config->overrides as $id => $config) {
            $cacheServiceId = CacheDriverInterface::class . ':' . $id;

            $builder->set($cacheServiceId)
                ->factory(new StaticMethodServiceFactory($config->driver, 'create'))
                ->arg(new Reference(CacheConfig::class)->property('overrides')->offset($id))
                ->arg(new Reference(ContainerInterface::class))
            ;

            $service = $builder->find($id);

            if ($service) {
                $this->setCacheService($service, $cacheServiceId);

                continue;
            }

            if (class_exists($id) || interface_exists($id)) {
                foreach ($builder->services as $service) {
                    if ($service->class !== null && ($service->class === $id || is_subclass_of($service->class, $id))) {
                        $this->setCacheService($service, $cacheServiceId);
                    }
                }
            }
        }
    }

    private function setCacheService(ServiceBuilder $service, string $cacheServiceId): void
    {
        /**
         * @var mixed $argument
         */
        foreach ($service->arguments as $index => $argument) {
            if (!$argument instanceof Reference) {
                continue;
            }

            if (in_array($argument->id, [CacheInterface::class, CacheDriverInterface::class], true)) {
                $service->arguments[$index] = new Reference($cacheServiceId);
            } elseif ($argument->id === CacheFetcher::class) {
                $service->arguments[$index] = new NewExpression(CacheFetcher::class, [new Reference($cacheServiceId)]);
            }
        }
    }
}
