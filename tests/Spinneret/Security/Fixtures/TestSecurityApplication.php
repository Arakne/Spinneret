<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Container\Argument\NewExpression;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Security\SecurityModule;
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
            new class extends AbstractModule {
                #[Override]
                protected function configure(): void
                {
                    $this->post('/login', LoginRequest::class, LoginPresenter::class);
                    $this->get('/user', ShowUserRequest::class, ShowUserPresenter::class);

                    $this->renderer(LoginResponse::class, LoginRenderer::class);
                    $this->renderer(ShowUserResponse::class, ShowUserRenderer::class);
                    $this->renderer(NotLoggedResponse::class, NotLoggedRenderer::class);

                    $this->autowire(TestUserHandler::class);

                    $this->service(Randomizer::class, [
                        new NewExpression(Xoshiro256StarStar::class, [123]),
                    ]);
                }

                protected function configureContainer(ContainerBuilder $containerBuilder): void
                {
                    $containerBuilder->register(ClockInterface::class)->factory(FixedClock::instance(...));
                }
            },
            new SecurityModule(),
        ];
    }
}
