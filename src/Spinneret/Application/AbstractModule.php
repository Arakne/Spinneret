<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Application\Attribute\ModuleAttributeInterface;
use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\ViewRendererInterface;
use FilesystemIterator;
use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_is_list;
use function array_merge_recursive;
use function assert;
use function class_exists;
use function interface_exists;
use function is_array;
use function ltrim;
use function str_replace;
use function strlen;
use function substr;
use function var_dump;

/**
 * Simple module implementation
 *
 * Simply override methods:
 * - configure() to register routes, presenters and renderers
 * - configureContainer() to register services in the container
 *
 * All renderers and presenters are automatically auto-wired and public.
 *
 * Example:
 * ```php
 * class MyModule extends AbstractModule
 * {
 *     #[Override]
 *     protected function configure(): void
 *     {
 *         // Register the hello route with it's linked presenter and renderer
 *         $this->get('/hello', HelloRequest::class, HelloPresenter::class);
 *         $this->renderer(HelloResponse::class, HelloRenderer::class);
 *     }
 *
 *     #[Override]
 *     protected function configureContainer(ContainerBuilder $containerBuilder): void
 *     {
 *         // Register a service in the container
 *         $containerBuilder->register(HelloService::class, HelloService::class)->setArguments([
 *             new Reference(HelloRepository::class),
 *         ]);
 *     }
 * }
 */
abstract class AbstractModule implements ModuleInterface
{
    /**
     * @var array<class-string, class-string<PresenterInterface>>
     */
    private array $presenters = [];

    /**
     * @var array<class-string, class-string<ViewRendererInterface>>
     */
    private array $renderers = [];

    /**
     * @var array<array{
     *     methods: list<string>,
     *     path: string,
     *     target: class-string,
     * }>
     */
    private array $routes = [];

    /**
     * @var array<class-string, array{
     *     params: list<mixed>,
     *     autowire: bool,
     *     public: bool,
     *     tags: array<array-key, string|array<string, scalar>|list<array<string, scalar>>>,
     *     aliases: list<class-string>,
     * }>
     */
    private array $services = [];

    /**
     * @var array<class-string, class-string>
     */
    private array $aliases = [];

    /**
     * Whether the module has been configured
     */
    private bool $configured = false;

    /**
     * Must be implemented by the child class to register
     */
    abstract protected function configure(): void;

    /**
     * To override to manually define services in the container.
     *
     * @param ContainerBuilder $containerBuilder
     * @return void
     */
    protected function configureContainer(ContainerBuilder $containerBuilder): void
    {
        // To override
    }

    #[Override]
    final public function register(ContainerBuilder $containerBuilder): void
    {
        $this->callConfigure();

        foreach ($this->services as $class => $arguments) {
            if ($containerBuilder->hasDefinition($class)) {
                $definition = $containerBuilder->getDefinition($class);
                $definition
                    ->setArguments($arguments['params'] + $definition->getArguments())
                    ->setPublic($definition->isPublic() || $arguments['public'])
                    ->setAutowired($definition->isAutowired() || $arguments['autowire'])
                ;
            } else {
                $definition = $containerBuilder->register($class, $class)
                    ->setArguments($arguments['params'])
                    ->setPublic($arguments['public'])
                    ->setAutowired($arguments['autowire'])
                    ->setAutoconfigured(true)
                ;
            }

            foreach ($arguments['tags'] as $name => $attributes) {
                if (is_array($attributes)) {
                    if (!array_is_list($attributes)) {
                        $attributes = [$attributes];
                    }

                    foreach ($attributes as $tagAttributes) {
                        /** @psalm-suppress PossiblyInvalidArgument */
                        $definition->addTag((string) $name, $tagAttributes);
                    }
                } else {
                    $definition->addTag($attributes);
                }
            }

            foreach ($arguments['aliases'] as $alias) {
                $containerBuilder
                    ->setAlias($alias, $class)
                    ->setPublic($arguments['public'])
                ;
            }
        }

        foreach ($this->aliases as $alias => $target) {
            $containerBuilder->setAlias($alias, $target);
        }

        foreach ($this->presenters as $requestClass => $presenterClass) {
            if (!$containerBuilder->hasDefinition($presenterClass)) {
                $definition = $containerBuilder->autowire($presenterClass, $presenterClass);
            } else {
                $definition = $containerBuilder->getDefinition($presenterClass);
            }

            $definition
                ->setPublic(true)
                ->addTag(PresenterInterface::class, ['request' => $requestClass])
            ;
        }

        foreach ($this->renderers as $responseClass => $rendererClass) {
            $containerBuilder
                ->autowire($rendererClass, $rendererClass)
                ->addTag(ViewRendererInterface::class, ['response' => $responseClass])
                ->setPublic(true)
            ;
        }

        $this->configureContainer($containerBuilder);
    }

    #[Override]
    final public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $this->callConfigure();

        foreach ($this->routes as ['methods' => $methods, 'path' => $path, 'target' => $target]) {
            $builder->add($path, $target, $methods);
        }
    }

    final public function path(string $path, string $namespace): void
    {
        if ($namespace !== '' && $namespace[-1] !== '\\') {
            $namespace .= '\\';
        }

        $pathLen = strlen($path);

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($it as $file) {
            assert($file instanceof SplFileInfo);

            $classBaseName = $file->getBasename('.php');
            $classNamespace = $namespace . str_replace('/', '\\', substr($file->getPath(), $pathLen + 1));

            if ($classNamespace !== '' && $classNamespace[-1] !== '\\') {
                $classNamespace .= '\\';
            }

            $className = ltrim($classNamespace, '\\') . $classBaseName;

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            // @todo automatically handle AsCommand ?
            foreach ($reflection->getAttributes(ModuleAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();
                assert($attribute instanceof ModuleAttributeInterface);
                $attribute->register($reflection, $this);
            }
        }
    }

    /**
     * Register a new GET route and presenter.
     *
     * @param string $path The URL path
     * @param class-string $target The request class name
     * @param class-string<PresenterInterface>|null $presenter The presenter class name
     *
     * @return void
     * @api
     */
    final public function get(string $path, string $target, ?string $presenter): void
    {
        $this->routes[] = [
            'methods' => ['GET'],
            'path' => $path,
            'target' => $target,
        ];

        if ($presenter !== null) {
            $this->presenter($target, $presenter);
        }
    }

    /**
     *  Register a new POST route and presenter.
     *
     * @param string $path The URL path
     * @param class-string $target The request class name
     * @param class-string<PresenterInterface>|null $presenter The presenter class name
     *
     * @return void
     * @api
     */
    final public function post(string $path, string $target, ?string $presenter): void
    {
        $this->routes[] = [
            'methods' => ['POST'],
            'path' => $path,
            'target' => $target,
        ];

        if ($presenter !== null) {
            $this->presenter($target, $presenter);
        }
    }

    /**
     * Register the renderer for a specific view.
     *
     * @param class-string<R> $response The response class name
     * @param class-string<ViewRendererInterface<R>> $renderer The renderer class name
     * @return void
     * @api
     *
     * @template R as object
     */
    final public function renderer(string $response, string $renderer): void
    {
        $this->renderers[$response] = $renderer;
    }

    /**
     * Register the presenter for a specific request.
     *
     * @param class-string<R> $request The request class name
     * @param class-string<PresenterInterface<R>> $presenter The presenter class name
     * @return void
     *
     * @see AbstractModule::get() To register a route and presenter for a GET request
     * @see AbstractModule::post() To register a route and presenter for a POST request
     * @api
     *
     * @template R as object
     */
    final public function presenter(string $request, string $presenter): void
    {
        $this->autowire($presenter, public: true, tags: [PresenterInterface::class => [['request' => $request]]]);
    }

    /**
     * Register a service in the container.
     *
     * This is equivalent to calling `$containerBuilder->register($class, $class)->setArguments($parameters);`
     * into the `configureContainer` method.
     *
     * @param class-string $class The service class name
     * @param list<mixed> $parameters The service arguments
     * @param bool $autowire Whether the service should be autowired
     * @param bool $public Whether the service should be public
     * @param array<array-key, string|array<string, scalar>|list<array<string, scalar>>> $tags The service tags
     * @param list<class-string> $aliases The service aliases
     *
     * @return void
     *
     * @see ContainerBuilder::register()
     *
     * @psalm-suppress PropertyTypeCoercion @todo: WIP - remove when new container is implemented
     */
    final public function service(string $class, array $parameters = [], bool $autowire = false, bool $public = false, array $tags = [], array $aliases = []): void
    {
        if (!isset($this->services[$class])) {
            $this->services[$class] = [
                'params' => $parameters,
                'autowire' => $autowire,
                'public' => $public,
                'tags' => $tags,
                'aliases' => $aliases,
            ];

            return;
        }

        $this->services[$class]['params'] = $parameters + $this->services[$class]['params'];
        $this->services[$class]['autowire'] = $autowire || $this->services[$class]['autowire'];
        $this->services[$class]['public'] = $public || $this->services[$class]['public'];
        $this->services[$class]['tags'] = array_merge_recursive($this->services[$class]['tags'], $tags);
        $this->services[$class]['aliases'] = [...$this->services[$class]['aliases'], ...$aliases];
    }

    /**
     * Autowire a service for the container.
     *
     * This is equivalent to calling `$containerBuilder->autowire($class, $class)->setPublic($public);`
     * into the `configureContainer` method.
     *
     * @param class-string $class The service class name
     * @param bool $public Whether the service should be public
     * @param array<array-key, string|array<string, scalar>|list<array<string, scalar>>> $tags The service tags
     * @param list<class-string> $aliases The service aliases
     *
     * @return void
     *
     * @see ContainerBuilder::register()
     */
    final public function autowire(string $class, bool $public = false, array $tags = [], array $aliases = []): void
    {
        $this->service($class, autowire: true, public: $public, tags: $tags, aliases: $aliases);
    }

    /**
     * Define an alias for a service.
     *
     * @param class-string $alias The alias name
     * @param class-string $target The target service name
     *
     * @return void
     */
    final public function alias(string $alias, string $target): void
    {
        $this->aliases[$alias] = $target;
    }

    private function callConfigure(): void
    {
        if (!$this->configured) {
            $this->configure();
            $this->configured = true;
        }
    }
}
