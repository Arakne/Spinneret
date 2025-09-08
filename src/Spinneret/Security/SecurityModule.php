<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Spinneret\Security\User\UserHandlerInterface;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Http\Server\MiddlewareInterface;
use Random\Randomizer;

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

        $containerBuilder->register(ObjectUserHandler::class);

        $containerBuilder->alias(UserHandlerInterface::class, $this->configuration->userHandler);
        $containerBuilder->alias(CookieSerializerInterface::class, $this->configuration->serializer);

        $containerBuilder->register(CookieOptions::class)
            ->shared(false)
            ->inline()
            ->value(new Reference(SecurityConfig::class)->property('cookie'))
        ;

        $containerBuilder->register(LoadSessionMiddleware::class)
            ->arg(new Reference(CookieSerializerInterface::class))
            ->arg(new Reference(AuthenticationCookieHelper::class))
            ->arg(new Reference(CookieOptions::class)->property('name'))
            ->arg(new Reference(SecurityConfig::class)->property('userAttribute'))
            ->tag(MiddlewareInterface::class)
        ;

        $containerBuilder->register(HmacCookieSerializer::class)
            ->factory(self::createHmacCookieSerializer(...))
            ->arg(new Reference(UserHandlerInterface::class))
            ->arg(new Reference(ClockInterface::class, nullOnInvalid: true))
            ->arg(new Reference(SecurityConfig::class))
        ;

        $containerBuilder->register(AuthenticationCookieHelper::class, [
            new Reference(SecurityConfig::class),
            new Reference(CookieSerializerInterface::class),
            new Reference(UserHandlerInterface::class),
            new Reference(Randomizer::class, nullOnInvalid: true),
            new Reference(ClockInterface::class, nullOnInvalid: true),
        ]);
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
