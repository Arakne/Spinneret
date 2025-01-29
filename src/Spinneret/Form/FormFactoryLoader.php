<?php

namespace Arakne\Spinneret\Form;

use Arakne\Spinneret\Application\Application;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\RegistryInterface;

/**
 * Load the form factory
 *
 * Use runtime form factory in dev mode
 * Use generated form factory in prod mode
 */
final readonly class FormFactoryLoader
{
    public function __construct(
        private RegistryInterface $registry,
    ) {}

    public function load(Application $application): FormFactoryInterface
    {
        if ($application->isDev) {
            return DefaultFormFactory::runtime($this->registry);
        }

        return DefaultFormFactory::generated(
            $this->registry,
            fn (string $className) => $application->cacheDir() . '/form/' . str_replace('\\', '_', $className) . '.php',
        );
    }
}
