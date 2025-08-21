<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\Security\SecurityModule;
use Arakne\Spinneret\View\Attribute\Renderer;
use Arakne\Tests\Spinneret\Security\Fixtures\Login\LoginPresenter;
use Arakne\Tests\Spinneret\Security\Fixtures\Login\LoginRenderer;
use Arakne\Tests\Spinneret\Security\Fixtures\Login\LoginRequest;
use Arakne\Tests\Spinneret\Security\Fixtures\Login\LoginResponse;
use Arakne\Tests\Spinneret\Security\Fixtures\ShowUser\NotLoggedRenderer;
use Arakne\Tests\Spinneret\Security\Fixtures\ShowUser\NotLoggedResponse;
use Arakne\Tests\Spinneret\Security\Fixtures\ShowUser\ShowUserPresenter;
use Arakne\Tests\Spinneret\Security\Fixtures\ShowUser\ShowUserRenderer;
use Arakne\Tests\Spinneret\Security\Fixtures\ShowUser\ShowUserRequest;
use Arakne\Tests\Spinneret\Security\Fixtures\ShowUser\ShowUserResponse;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use Override;
use Psr\Clock\ClockInterface;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

class TestSecurityApplication extends Application
{
    public function configDir(): string
    {
        return __DIR__ . '/config';
    }

    #[Override]
    protected function applicationModules(): array
    {
        return [
            new class implements ModuleInterface, RouteConfiguratorInterface {
                public function register(ContainerBuilder $containerBuilder): void
                {
                    $containerBuilder->register(LoginPresenter::class)->tag(new Presenter(LoginRequest::class));
                    $containerBuilder->register(ShowUserPresenter::class)->tag(new Presenter(ShowUserRequest::class));

                    $containerBuilder->register(LoginRenderer::class)->tag(new Renderer(LoginResponse::class));
                    $containerBuilder->register(ShowUserRenderer::class)->tag(new Renderer(ShowUserResponse::class));
                    $containerBuilder->register(NotLoggedRenderer::class)->tag(new Renderer(NotLoggedResponse::class));

                    $containerBuilder->register(TestUserHandler::class);
                    $containerBuilder->register(Randomizer::class, [new NewExpression(Xoshiro256StarStar::class, [123])]);
                    $containerBuilder->register(ClockInterface::class)->factory(FixedClock::instance(...));
                }

                #[Override]
                public function configureRoutes(RouteCollectionBuilder $builder): void
                {
                    $builder->post('/login', LoginRequest::class);
                    $builder->get('/user', ShowUserRequest::class);
                }
            },
            new SecurityModule(),
        ];
    }
}
