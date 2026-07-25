<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use Override;
use Psr\Clock\ClockInterface;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

final class TestModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        // Make all services public
        $containerBuilder->processor(new class() implements ContainerBuilderProcessorInterface {
            #[Override]
            public function process(ContainerBuilder $builder): void
            {
                foreach ($builder->services as $service) {
                    $service->public = true;
                }
            }
        });

        $containerBuilder->register(ClockInterface::class)
            ->factory(FixedClock::instance(...))
        ;

        $containerBuilder->register(Randomizer::class, [new NewExpression(Xoshiro256StarStar::class, [123])]);
    }
}
