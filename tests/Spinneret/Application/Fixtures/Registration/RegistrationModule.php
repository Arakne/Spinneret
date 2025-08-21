<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Constraint\UniqueNameValidator;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain\UserRepository;
use Override;

final class RegistrationModule implements ModuleInterface, RouteConfiguratorInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(RegistrationPresenter::class)->tag(new Presenter(RegistrationRequest::class));
        $containerBuilder->register(RegistrationSuccessRenderer::class)->tag(new Renderer(RegistrationSuccessResponse::class));
        $containerBuilder->register(RegistrationErrorRenderer::class)->tag(new Renderer(RegistrationErrorResponse::class));
        $containerBuilder->register(UniqueNameValidator::class)->public();
        $containerBuilder->register(UserRepository::class)->public();
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $builder->post('/register', RegistrationRequest::class);
    }
}
