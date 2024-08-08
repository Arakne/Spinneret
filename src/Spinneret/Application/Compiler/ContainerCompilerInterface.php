<?php

namespace Arakne\Spinneret\Application\Compiler;

use Arakne\Spinneret\Application\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface for load and compile the container
 */
interface ContainerCompilerInterface
{
    /**
     * Try to load the compiled container from the cache
     * If some error occurs during the loading, the method should return null instead of throwing an exception
     *
     * @param Application $application The application
     *
     * @return ContainerInterface|null The compiled container or null if it is not found, or cannot be loaded
     */
    public function load(Application $application): ?ContainerInterface;

    /**
     * Compile and save the given container
     *
     * After calling this method, {@see ContainerCompilerInterface::load()} should return the compiled container.
     * If a compiled container already exists, it should be replaced by the new one.
     *
     * @param Application $application The application
     * @param ContainerBuilder $container The built container
     *
     * @return void
     */
    public function compile(Application $application, ContainerBuilder $container): void;
}
