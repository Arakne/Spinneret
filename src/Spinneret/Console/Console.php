<?php

namespace Arakne\Spinneret\Console;

use Arakne\Spinneret\Application\Application as SpinneretApplication;
use Arakne\Spinneret\SpinneretVersion;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

/**
 * Base application wrapper for the console.
 *
 * @api
 */
class Console extends ConsoleApplication
{
    public function __construct(
        public readonly SpinneretApplication $application,
        private readonly array $commandMap,
    ) {
        // @todo resolve application name and version
        parent::__construct('Spinneret', SpinneretVersion::FULL);

        $this->setCommandLoader(new ContainerCommandLoader($this->application, $this->commandMap));
    }
}
