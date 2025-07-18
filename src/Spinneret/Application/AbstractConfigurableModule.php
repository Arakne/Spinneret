<?php

namespace Arakne\Spinneret\Application;

use Override;

/**
 * Module implementation with configuration
 *
 * Simply override methods:
 * - configure() to register routes, presenters and renderers
 * - configureContainer() to register services in the container
 * - defaultConfiguration() to create the default configuration object
 *
 * @template C as object
 * @implements ConfigurableModuleInterface<C>
 *
 * @psalm-consistent-constructor
 */
abstract class AbstractConfigurableModule extends AbstractModule implements ConfigurableModuleInterface
{
    final protected function __construct(
        /**
         * The current configuration object
         *
         * @var C
         */
        private object $configuration,
    ) {}

    #[Override]
    final public function withConfiguration(object $configuration): static
    {
        $self = clone $this;
        $self->configuration = $configuration;

        return $self;
    }

    #[Override]
    final public function configuration(): object
    {
        return $this->configuration;
    }

    /**
     * Create the default configuration object
     *
     * @return C
     */
    abstract protected static function defaultConfiguration(Application $app): object;

    /**
     * Create the module with the default configuration
     *
     * @param Application $app The application instance. Used to access application settings.
     *
     * @return static
     * @psalm-suppress UnsafeGenericInstantiation
     */
    public static function create(Application $app): static
    {
        return new static(static::defaultConfiguration($app));
    }
}
