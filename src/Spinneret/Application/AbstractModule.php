<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function __construct()
    {
        $this->configure();
    }

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
        foreach ($this->presenters as $presenterClass) {
            $containerBuilder->autowire($presenterClass, $presenterClass)->setPublic(true);
        }

        foreach ($this->renderers as $rendererClass) {
            $containerBuilder->autowire($rendererClass, $rendererClass)->setPublic(true);
        }

        $this->configureContainer($containerBuilder);
    }

    #[Override]
    final public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        foreach ($this->routes as ['methods' => $methods, 'path' => $path, 'target' => $target]) {
            $builder->add($path, $target, $methods);
        }
    }

    #[Override]
    final public function presenters(): array
    {
        return $this->presenters;
    }

    #[Override]
    final public function renderers(): array
    {
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
}
