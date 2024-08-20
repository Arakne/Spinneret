<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Application\Compiler\ContainerCompiler;
use Arakne\Spinneret\Application\Compiler\ContainerCompilerInterface;
use Arakne\Spinneret\Application\Config\ConfigLoaderInterface;
use Arakne\Spinneret\Application\Config\PhpConfigLoader;
use Arakne\Spinneret\Error\ErrorModule;
use Arakne\Spinneret\Form\FormModule;
use Arakne\Spinneret\Presenter\PresenterModule;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Router\RouterModule;
use Arakne\Spinneret\Runner\RunnerInterface;
use Arakne\Spinneret\Util\Project;
use Arakne\Spinneret\View\ViewModule;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainer;

/**
 * Base class for a Spinneret application
 * Should be extended by the application class
 *
 * @api
 */
class Application implements RunnerInterface
{
    private readonly ContainerInterface $container;
    private readonly RunnerInterface $runner;

    /**
     * @var list<ModuleInterface>|null
     */
    private ?array $modules = null;

    /**
     * @var array<class-string, object>|null
     * @psalm-var class-string-map<T, T>|null
     */
    private ?array $config = null;

    public function __construct(
        /**
         * Whether the application is in development mode
         * If true, all caches are disabled
         */
        public readonly bool $isDev = false,

        /**
         * Strategy to load or compile the container
         * If null, the container is not compiled
         */
        private readonly ?ContainerCompilerInterface $containerCompiler = new ContainerCompiler(),

        /**
         * Strategy to load the configuration
         * By default, will load all *.php files from the config directory
         *
         * @var ConfigLoaderInterface
         */
        private readonly ConfigLoaderInterface $configLoader = new PhpConfigLoader(),
    ) {
        $this->container = $this->loadContainer();
        /** @psalm-suppress MixedAssignment : The service RunnerInterface may be overridden, but it will raise an error anyway */
        $this->runner = $this->container->get(RunnerInterface::class);
    }

    #[Override]
    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->runner->handle($request);
    }

    #[Override]
    final public function handleRoutedRequest(RoutedRequest $routedRequest, bool $catch = true): ResponseInterface
    {
        return $this->runner->handleRoutedRequest($routedRequest, $catch);
    }

    /**
     * List of enabled modules
     *
     * This list contains the default modules and the modules provided by the application.
     * Modules instances will be created only once, so the exact same list will be returned on each call.
     *
     * @return list<ModuleInterface>
     */
    final public function modules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = [
            new RouterModule(),
            new PresenterModule(),
            new ViewModule(),
            new FormModule(),
            new ErrorModule(),
            new ApplicationModule(),
            ...$this->applicationModules(),
        ];

        $config = $this->config();

        foreach ($modules as $i => $module) {
            if ($module instanceof ConfigurableModuleInterface) {
                $configItem = $config[$module->configuration()::class] ?? null;

                if ($configItem) {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $modules[$i] = $module->withConfiguration($configItem);
                }
            }
        }

        return $this->modules = $modules;
    }

    /**
     * Load configuration objects from the config directory
     *
     * The returned array is indexed by the class name of the configuration object.
     * Config instances will be created only once, so the exact same array will be returned on each call.
     *
     * @return array<class-string, object>
     * @psalm-return class-string-map<T, T>
     */
    final public function config(): array
    {
        /**
         * @var class-string-map<T, T>
         * @psalm-suppress MixedAssignment
         */
        return $this->config ??= $this->configLoader->load($this);
    }

    /**
     * To override in the application class to define additional modules
     *
     * @return list<ModuleInterface>
     */
    protected function applicationModules(): array
    {
        return [];
    }

    /**
     * Get the project root directory
     *
     * This directory is used to get all other directories like cache, logs, etc.
     * By default, the project root directory is the directory containing the composer.json file.
     *
     * @return string
     */
    public function projectDir(): string
    {
        return Project::directory(static::class);
    }

    /**
     * Get the directory where cache files are stored
     *
     * By default, the cache directory is var/cache in the project root directory.
     *
     * @return string
     */
    public function cacheDir(): string
    {
        return $this->projectDir().'/var/cache';
    }

    /**
     * Get the directory where configurations files are stored
     *
     * By default, the cache directory is config in the project root directory.
     *
     * @return string
     */
    public function configDir(): string
    {
        return $this->projectDir().'/config';
    }

    /**
     * Load the container
     *
     * When {@see Application::isDev} is true, the container is always reloaded.
     * Otherwise, the container is reloaded only if the compiled container file is not found.
     *
     * @return ContainerInterface
     */
    private function loadContainer(): ContainerInterface
    {
        $container = !$this->isDev ? $this->containerCompiler?->load($this) : null;
        $container ??= $this->buildContainer(); // Container not found or in dev mode : build it

        $container->set(Application::class, $this);

        foreach ($this->modules() as $module) {
            if ($module instanceof ConfigurableModuleInterface) {
                $config = $module->configuration();
                $container->set($config::class, $config);
            }
        }

        return $container;
    }

    /**
     * Create the container, and compile it
     *
     * @return SymfonyContainer
     */
    public function buildContainer(): SymfonyContainer
    {
        $containerBuilder = new ContainerBuilder();

        $renderers = [];
        $presenters = [];

        foreach ($this->modules() as $module) {
            $module->register($containerBuilder);

            $presenters += $module->presenters();
            $renderers += $module->renderers();

            if ($module instanceof ConfigurableModuleInterface) {
                $config = $module->configuration();
                $containerBuilder->register($config::class)->setSynthetic(true);
            }
        }

        $containerBuilder->setParameter(ViewModule::RENDERERS_PARAMETER, $renderers);
        $containerBuilder->setParameter(PresenterModule::PRESENTERS_PARAMETER, $presenters);

        $containerBuilder->compile();
        $this->containerCompiler?->compile($this, $containerBuilder);

        return $containerBuilder;
    }
}
