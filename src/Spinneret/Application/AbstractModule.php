<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

use function is_array;

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
     *     tags: array<array-key, string|array<string, scalar>>,
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

        foreach ($this->presenters as $presenterClass) {
            $containerBuilder->autowire($presenterClass, $presenterClass)->setPublic(true);
        }

        foreach ($this->renderers as $rendererClass) {
            $containerBuilder->autowire($rendererClass, $rendererClass)->setPublic(true);
        }

        foreach ($this->services as $class => $arguments) {
            $definition = $containerBuilder->register($class, $class)
                ->setArguments($arguments['params'])
                ->setPublic($arguments['public'])
                ->setAutowired($arguments['autowire'])
                ->setAutoconfigured(true)
            ;

            foreach ($arguments['tags'] as $name => $attributes) {
                if (is_array($attributes)) {
                    $definition->addTag((string) $name, $attributes);
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

    #[Override]
    final public function presenters(): array
    {
        $this->callConfigure();

        return $this->presenters;
    }

    #[Override]
    final public function renderers(): array
    {
        $this->callConfigure();

        return $this->renderers;
    }

    /**
     * Register a new GET route and presenter.
     *
     * @param string $path The URL path
     * @param class-string $target The request class name
     * @param class-string<PresenterInterface> $presenter The presenter class name
     *
     * @return void
     * @api
     */
    final protected function get(string $path, string $target, string $presenter): void
    {
        $this->routes[] = [
            'methods' => ['GET'],
            'path' => $path,
            'target' => $target,
        ];

        $this->presenter($target, $presenter);
    }

    /**
     *  Register a new POST route and presenter.
     *
     * @param string $path The URL path
     * @param class-string $target The request class name
     * @param class-string<PresenterInterface> $presenter The presenter class name
     *
     * @return void
     * @api
     */
    final protected function post(string $path, string $target, string $presenter): void
    {
        $this->routes[] = [
            'methods' => ['POST'],
            'path' => $path,
            'target' => $target,
        ];

        $this->presenter($target, $presenter);
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
    final protected function renderer(string $response, string $renderer): void
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
    final protected function presenter(string $request, string $presenter): void
    {
        $this->presenters[$request] = $presenter;
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
     * @param array<array-key, string|array<string, scalar>> $tags The service tags
     * @param list<class-string> $aliases The service aliases
     *
     * @return void
     *
     * @see ContainerBuilder::register()
     */
    final protected function service(string $class, array $parameters = [], bool $autowire = false, bool $public = false, array $tags = [], array $aliases = []): void
    {
        $this->services[$class] = [
            'params' => $parameters,
            'autowire' => $autowire,
            'public' => $public,
            'tags' => $tags,
            'aliases' => $aliases,
        ];
    }

    /**
     * Autowire a service for the container.
     *
     * This is equivalent to calling `$containerBuilder->autowire($class, $class)->setPublic($public);`
     * into the `configureContainer` method.
     *
     * @param class-string $class The service class name
     * @param bool $public Whether the service should be public
     * @param array<array-key, string|array<string, scalar>> $tags The service tags
     * @param list<class-string> $aliases The service aliases
     *
     * @return void
     *
     * @see ContainerBuilder::register()
     */
    final protected function autowire(string $class, bool $public = false, array $tags = [], array $aliases = []): void
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
    final protected function alias(string $alias, string $target): void
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

/**
 * Helper function to create a new service reference.
 *
 * @param string $id The service identifier
 * @return Reference
 */
function service(string $id): Reference
{
    return new Reference($id);
}

/**
 * Helper function to create a new service reference, which can be null if the service is not found.
 *
 * @param string $id The service identifier
 * @return Reference
 */
function service_nullable(string $id): Reference
{
    return new Reference($id, ContainerInterface::NULL_ON_INVALID_REFERENCE);
}

/**
 * Inject to service parameter an iterable of services with a specific tag.
 *
 * @param string $tag The tag name
 * @return TaggedIteratorArgument
 */
function tagged_services(string $tag): TaggedIteratorArgument
{
    return new TaggedIteratorArgument($tag);
}

/**
 * Helper function to create a service resolver closure.
 *
 * @param string $id The service identifier
 * @return ServiceClosureArgument
 */
function service_closure(string $id): ServiceClosureArgument
{
    return new ServiceClosureArgument(service($id));
}
