<?php

namespace Arakne\Spinneret\Error;

use Arakne\Spinneret\Application\AbstractConfigurableModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\BootableModuleInterface;
use Arakne\Spinneret\Logger\LoggerModule;
use Arakne\Spinneret\Runner\InternalServerError;
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
 * @extends AbstractConfigurableModule<ErrorConfiguration>
 */
final class ErrorModule extends AbstractConfigurableModule implements BootableModuleInterface
{
    #[Override]
    public function boot(Application $application): void
    {
        $application->get(ErrorHandler::class)->register();
    }

    #[Override]
    protected function configure(): void
    {
        $this->presenter(InternalServerError::class, ErrorPresenter::class);
        $this->renderer(InternalServerError::class, InternalServerErrorRenderer::class);

        $this->service(ErrorHandler::class, [
            service(ErrorConfiguration::class),
            service_nullable(LoggerInterface::class),
        ], public: true);
    }

    #[Override]
    protected static function defaultConfiguration(Application $app): object
    {
        return new ErrorConfiguration();
    }
}
