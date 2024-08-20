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
 */
abstract class AbstractConfigurableModule extends AbstractModule implements ConfigurableModuleInterface
{
    /**
     * The current configuration object
     *
     * @var C|null
     */
    private ?object $configuration = null;

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
        return $this->configuration ??= $this->defaultConfiguration();
    }

    /**
     * Create the default configuration object
     *
     * @return C
     */
    abstract protected function defaultConfiguration(): object;
}
