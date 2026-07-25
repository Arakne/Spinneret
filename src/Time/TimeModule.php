<?php

namespace Arakne\Spinneret\Time;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use DateTimeZone;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Module providing time-related services, and clock implementation.
 *
 * @implements ConfigurableModuleInterface<TimeConfiguration>
 */
final readonly class TimeModule implements ConfigurableModuleInterface
{
    public function __construct(
        private TimeConfiguration $config = new TimeConfiguration(),
    ) {}

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(SystemClock::class, [new Reference(DateTimeZone::class)]);
        $containerBuilder->register(DateTimeZone::class)
            ->value(new Reference(TimeConfiguration::class)->property('timeZone'))
            ->shared(false)
            ->inline()
        ;

        if ($this->config->fixedTime !== null) {
            $containerBuilder->register(FixedClock::class, [
                new Reference(TimeConfiguration::class)->property('fixedTime'),
                new Reference(DateTimeZone::class),
            ]);

            $containerBuilder->alias(ClockInterface::class, FixedClock::class);
        } else {
            $containerBuilder->alias(ClockInterface::class, SystemClock::class);
        }
    }

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): object
    {
        return $this->config;
    }
}
