<?php

namespace Arakne\Tests\Spinneret\Security\Attribute;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Security\Attribute\UserAccessor;
use Arakne\Spinneret\Security\SecurityModule;
use Arakne\Spinneret\Security\User\AuthenticatedUserAccessor;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;
use Nyholm\Psr7\ServerRequest;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserAccessorTest extends TestCase
{
    #[Test]
    public function test()
    {
        $app = new class('test-userAccessor', true) extends Application {
            protected function applicationModules(): array
            {
                return [
                    new SecurityModule(),
                    new class implements ModuleInterface {
                        #[Override]
                        public function register(ContainerBuilder $containerBuilder): void
                        {
                            $containerBuilder->register(UserAccessorContainer::class)->public();
                        }
                    }
                ];
            }
        };

        $psrRequest = new ServerRequest('GET', '/');
        $user1 = new TestUser('user1', 'password1');
        $user2 = (object) ['name' => 'user2'];
        $container = $app->get(UserAccessorContainer::class);

        $this->assertNull($container->accessor->get($psrRequest));
        $this->assertSame($user1, $container->accessor->get($psrRequest->withAttribute('user', $user1)));
        $this->assertSame($user2, $container->accessor->get($psrRequest->withAttribute('user', $user2)));

        $this->assertNull($container->accessorWithClassName->get($psrRequest));
        $this->assertSame($user1, $container->accessorWithClassName->get($psrRequest->withAttribute('user', $user1)));
        $this->assertNull($container->accessorWithClassName->get($psrRequest->withAttribute('user', $user2)));
    }

    #[Test]
    public function testCompiled()
    {
        $app = new class('test-userAccessor', false) extends Application {
            protected function applicationModules(): array
            {
                return [
                    new SecurityModule(),
                    new class implements ModuleInterface {
                        #[Override]
                        public function register(ContainerBuilder $containerBuilder): void
                        {
                            $containerBuilder->register(UserAccessorContainer::class)->public();
                        }
                    }
                ];
            }
        };

        $psrRequest = new ServerRequest('GET', '/');
        $user1 = new TestUser('user1', 'password1');
        $user2 = (object) ['name' => 'user2'];
        $container = $app->get(UserAccessorContainer::class);

        $this->assertNull($container->accessor->get($psrRequest));
        $this->assertSame($user1, $container->accessor->get($psrRequest->withAttribute('user', $user1)));
        $this->assertSame($user2, $container->accessor->get($psrRequest->withAttribute('user', $user2)));

        $this->assertNull($container->accessorWithClassName->get($psrRequest));
        $this->assertSame($user1, $container->accessorWithClassName->get($psrRequest->withAttribute('user', $user1)));
        $this->assertNull($container->accessorWithClassName->get($psrRequest->withAttribute('user', $user2)));
    }
}

class UserAccessorContainer
{
    public function __construct(
        #[UserAccessor]
        public AuthenticatedUserAccessor $accessor,
        #[UserAccessor(TestUser::class)]
        public AuthenticatedUserAccessor $accessorWithClassName,
    ) {}
}
