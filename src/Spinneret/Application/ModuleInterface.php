<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\ViewRendererInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ModuleInterface
{
    public function register(ContainerBuilder $containerBuilder): void;
    public function configureRoutes(RouteCollectionBuilder $builder): void;

    /**
     * @return array<class-string, class-string<PresenterInterface>>
     */
    public function presenters(): array;

    /**
     * @return array<class-string, class-string<ViewRendererInterface>>
     */
    public function renderers(): array;
}
