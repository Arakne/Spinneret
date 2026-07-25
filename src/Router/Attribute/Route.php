<?php

namespace Arakne\Spinneret\Router\Attribute;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Router\RouteCollectionLoader;
use Attribute;
use Override;

/**
 * Mark a request DTO class as a route.
 *
 * The route will be automatically registered in the router
 * when the DTO class will be imported in the container.
 *
 * Usage:
 * ```php
 * #[Route('/path', ['GET', 'POST'])]
 * final class MyRequest {}
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class Route implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        public string $path,
        /** @var list<string> */
        public array $methods = [],
        /** @var class-string|null */
        public ?string $fieldsExtractor = null,
    ) {}

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $loader = $container->find(RouteCollectionLoader::class);

        if (!$loader) {
            return;
        }

        /** @var mixed $routes */
        $routes = $loader->arguments[0] ?? [];

        if ($routes instanceof DynamicArray) {
            $routes = $routes->values;
        } elseif (!is_array($routes)) {
            $routes = [];
        }

        $routes[] = [
            $this->path,
            $service->class,
            $this->methods,
            $this->fieldsExtractor,
        ];

        $loader->arguments[0] = $routes;
    }
}
