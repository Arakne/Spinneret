<?php

namespace Arakne\Spinneret\Error;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\BootableModuleInterface;
use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Logger\LoggerModule;
use Override;
use Psr\Log\LoggerInterface;

use function Arakne\Spinneret\Application\service;
use function Arakne\Spinneret\Application\service_nullable;

/**
 * Register basic error handling functionality.
 *
 * Optional services:
 * - {@see LoggerInterface} to log errors.
 *
 * Provided services:
 * - {@see ErrorHandler} to handle errors and exceptions (public).
 *
 * @see LoggerModule To enable logging.
 *
 * @implements ConfigurableModuleInterface<ErrorConfiguration>
 */
final readonly class ErrorModule implements ConfigurableModuleInterface, BootableModuleInterface
{
    public function __construct(
        private ErrorConfiguration $configuration = new ErrorConfiguration(),
    ) {}

    #[Override]
    public function boot(Application $application): void
    {
        $application->get(ErrorHandler::class)->register();
    }

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): object
    {
        return $this->configuration;
    }

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(ErrorPresenter::class);
        $containerBuilder->register(InternalServerErrorRenderer::class);

        $containerBuilder
            ->register(
                ErrorHandler::class,
                [
                    service(ErrorConfiguration::class),
                    service_nullable(LoggerInterface::class),
                ]
            )
            ->public()
        ;
    }
}
