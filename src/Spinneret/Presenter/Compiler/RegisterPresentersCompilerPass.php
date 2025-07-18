<?php

namespace Arakne\Spinneret\Presenter\Compiler;

use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Arakne\Spinneret\Presenter\PresenterInterface;
use InvalidArgumentException;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function assert;
use function is_array;
use function is_string;
use function sprintf;

final readonly class RegisterPresentersCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $dispatcherDefinition = $container->getDefinition(PresenterDispatcher::class);
        $presenters = [];

        foreach ($container->findTaggedServiceIds(PresenterInterface::class) as $id => $tags) {
            $container->findDefinition($id)->setPublic(true);

            /** @var array<string, mixed> $tag */
            foreach ($tags as $tag) {
                if (!isset($tag['request']) || !is_string($tag['request'])) {
                    throw new InvalidArgumentException(sprintf(
                        'The "request" attribute of the "%s" tag on service "%s" must be a string.',
                        PresenterInterface::class,
                        $id
                    ));
                }

                $presenters[$tag['request']] = $id;
            }
        }

        $dispatcherDefinition->setArgument(1, $presenters);
    }
}
