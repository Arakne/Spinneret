<?php

namespace Arakne\Spinneret\Database\Compiler;

use Arakne\Spinneret\Database\DatabaseConnectionInterface;
use Arakne\Spinneret\Database\DatabaseConnectionManager;
use LogicException;
use Override;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function sprintf;

/**
 * Compiler pass to inject the database connection in the repositories
 */
final readonly class SetConnectionCompilerPass implements CompilerPassInterface
{
    public const string TAG = 'spinneret.db.repository';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            /** @var string $connectionName */
            $connectionName = $tags[0]['connection'] ?? throw new LogicException(sprintf('Service "%s" tagged with "%s" must have a "connection" attribute', $id, self::TAG));
            $definition = $container->getDefinition($id);

            /** @var class-string $class */
            $class = $definition->getClass() ?? $id;
            $reflection = (new ReflectionClass($class))->getConstructor();

            if (!$reflection) {
                continue;
            }

            foreach ($reflection->getParameters() as $pos => $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                if ($type->getName() === DatabaseConnectionInterface::class) {
                    $definition->setArgument(
                        $pos,
                        (new Definition(DatabaseConnectionInterface::class))
                            ->setFactory([new Reference(DatabaseConnectionManager::class), 'get'])
                            ->setArgument(0, $connectionName)
                    );
                }
            }
        }
    }
}
