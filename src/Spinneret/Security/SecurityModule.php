<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Spinneret\Security\User\UserHandlerInterface;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Http\Server\MiddlewareInterface;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module for enable simple session system and user storage.
 *
 * Provided services:
 * - {@see UserHandlerInterface} - Alias to the configured user handler. By default, {@see ObjectUserHandler} is used.
 * - {@see CookieSerializerInterface} - Alias to the configured cookie serializer. By default, {@see HmacCookieSerializer} is used.
 * - {@see AuthenticationCookieHelper} - Helper for working with authentication cookies.
 * - {@see LoadSessionMiddleware} - Middleware that loads the session from a cookie. Will be used by the runner.
 *
 * Optional dependencies:
 * - {@see ClockInterface} - Used for generate token timestamps and validate them. If not provided, will use the system clock.
 * - {@see Randomizer} - Used for generate random tokens. If not provided, will use a secure random generator.
 *
 * @implements ConfigurableModuleInterface<SecurityConfig>
 */
final readonly class SecurityModule implements ConfigurableModuleInterface
{
    public function __construct(
        private SecurityConfig $configuration = new SecurityConfig(),
    ) {}

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): SecurityConfig
    {
        return $this->configuration;
    }

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        if (!$this->configuration->enabled) {
            return;
        }

        $containerBuilder->register(ObjectUserHandler::class, ObjectUserHandler::class);

        $containerBuilder->setAlias(UserHandlerInterface::class, $this->configuration->userHandler);
        $containerBuilder->setAlias(CookieSerializerInterface::class, $this->configuration->serializer);

        $containerBuilder->register(LoadSessionMiddleware::class, LoadSessionMiddleware::class)
            ->setFactory([self::class, 'createUserMiddleware'])
            ->setArguments([
                new Reference(CookieSerializerInterface::class),
                new Reference(AuthenticationCookieHelper::class),
                new Reference(SecurityConfig::class),
            ])
            ->addTag(MiddlewareInterface::class)
        ;

        $containerBuilder->register(HmacCookieSerializer::class, HmacCookieSerializer::class)
            ->setFactory([self::class, 'createHmacCookieSerializer'])
            ->setArguments([
                new Reference(UserHandlerInterface::class),
                new Reference(ClockInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference(SecurityConfig::class),
            ])
        ;

        $containerBuilder->register(AuthenticationCookieHelper::class, AuthenticationCookieHelper::class)
            ->setArguments([
                new Reference(SecurityConfig::class),
                new Reference(CookieSerializerInterface::class),
                new Reference(Randomizer::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference(ClockInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    public static function createUserMiddleware(CookieSerializerInterface $serializer, AuthenticationCookieHelper $cookieHelper, SecurityConfig $config): LoadSessionMiddleware
    {
        return new LoadSessionMiddleware($serializer, $cookieHelper, $config->cookie->name, $config->userAttribute);
    }

    public static function createHmacCookieSerializer(UserHandlerInterface $userHandler, ?ClockInterface $clock, SecurityConfig $config): HmacCookieSerializer
    {
        // @todo secret not empty
        return new HmacCookieSerializer($userHandler, $config->secret ?? '', $config->version, 'sha512', true, $clock);
    }
}
