<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Http\Server\MiddlewareInterface;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @implements ConfigurableModuleInterface<SecurityConfig>
 */
final readonly class SecurityModule implements ConfigurableModuleInterface
{
    public function __construct(
        private SecurityConfig $configuration = new SecurityConfig(),
    ) {
    }

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

        $containerBuilder->setAlias(UserHandlerInterface::class, $this->configuration->userHandler);
        $containerBuilder->setAlias(CookieSerializerInterface::class, $this->configuration->serializer);

        $containerBuilder->register(LoadUserMiddleware::class, LoadUserMiddleware::class)
            ->setFactory([self::class, 'createUserMiddleware'])
            ->setArguments([
                new Reference(CookieSerializerInterface::class),
                new Reference(SecurityConfig::class),
            ])
            ->addTag(MiddlewareInterface::class)
        ;

        $containerBuilder->register(HmacCookieSerializer::class, HmacCookieSerializer::class)
            ->setFactory([self::class, 'createHmacCookieSerializer'])
            ->setArguments([
                new Reference(UserHandlerInterface::class),
                new Reference(Randomizer::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference(ClockInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference(SecurityConfig::class),
            ])
        ;
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    #[Override]
    public function presenters(): array
    {
        return [];
    }

    #[Override]
    public function renderers(): array
    {
        return [];
    }

    public static function createUserMiddleware(CookieSerializerInterface $serializer, SecurityConfig $config): LoadUserMiddleware
    {
        return new LoadUserMiddleware($serializer, $config->cookieName, LoadUserMiddleware::ATTRIBUTE_NAME); // @todo make attribute name configurable
    }

    public static function createHmacCookieSerializer(UserHandlerInterface $userHandler, ?Randomizer $randomizer, ?ClockInterface $clock, SecurityConfig $config): HmacCookieSerializer
    {
        // @todo secret not empty
        return new HmacCookieSerializer($userHandler, $config->secret ?? '', 1, 'sha512', true, 3600, $randomizer, $clock);
    }
}
