<?php

namespace Arakne\Tests;

use Arakne\Spinneret\Error\ErrorHandler;
use Override;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class RestoreErrorHandlerExtension implements FinishedSubscriber, Extension
{
    #[Override]
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber($this);
    }

    #[Override]
    public function notify(Finished $event): void
    {
        ErrorHandler::restore();
    }
}
