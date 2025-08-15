<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\Attribute\Renderer;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

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
     *     tags: list<string|object>,
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
     * List of paths to import in the container.
     *
     * @var list<list{string, string}>
     */
    private array $paths = [];

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

        foreach ($this->paths as [$path, $namespace]) {
            $containerBuilder->import($path, $namespace);
        }

        foreach ($this->services as $class => $arguments) {
            if ($containerBuilder->defined($class)) {
                $definition = $containerBuilder->services[$class];
                /** @psalm-suppress PropertyTypeCoercion */
                $definition->arguments = $arguments['params'] + $definition->arguments;
                $definition->public = $definition->public || $arguments['public'];
            } else {
                $definition = $containerBuilder->register($class);
                $definition->arguments = $arguments['params'];
                $definition->public = $definition->public || $arguments['public'];
            }

            foreach ($arguments['tags'] as $tag) {
                $definition->tag($tag);
            }

            foreach ($arguments['aliases'] as $alias) {
                $containerBuilder->alias($alias, $class);
            }
        }

        foreach ($this->aliases as $alias => $target) {
            $containerBuilder->alias($alias, $target);
        }

        foreach ($this->presenters as $requestClass => $presenterClass) {
            if (!$containerBuilder->defined($presenterClass)) {
                $definition = $containerBuilder->register($presenterClass);
            } else {
                $definition = $containerBuilder->services[$presenterClass];
            }

            $definition
                ->public()
                ->tag(new Presenter($requestClass))
            ;
        }

        foreach ($this->renderers as $responseClass => $rendererClass) {
            $definition = $containerBuilder->register($rendererClass);
            $definition
                ->public()
                ->tag(new Renderer($responseClass))
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

    /**
     * Load classes from the given directory path.
     *
     * @param string $path The directory path to load classes from
     * @param string $namespace The namespace to use for the classes in the given path
     *
     * @return void
     * @see ContainerBuilder::import()
     */
    final public function path(string $path, string $namespace): void
    {
        $this->paths[] = [$path, $namespace];
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
        $this->autowire($presenter, public: true, tags: [new Presenter($request)]);
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
     * @param list<string|object> $tags The service tags
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
        $this->services[$class]['tags'] = [...$this->services[$class]['tags'], ...$tags];
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
     * @param list<string|object> $tags The service tags
     * @param list<class-string> $aliases The service aliases
     *
     * @return void
     *
     * @see ContainerBuilder::register()
     * @todo remove: autowire is always enabled
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
