<?php

namespace Arakne\Spinneret\Database\Compiler;

use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\DatabaseConnectionManager;
use Override;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final readonly class SetConnectionCompilerPass implements CompilerPassInterface
{
    public const string TAG = 'repository';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $connectionName = $tags[0]['connection'];
            $definition = $container->getDefinition($id);

            $class = $definition->getClass() ?? $id;
            $reflection = (new ReflectionClass($class))->getConstructor();
            // @todo handle error

            foreach ($reflection->getParameters() as $pos => $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                if ($type->getName() === DatabaseConnection::class) {
                    $definition->setArgument(
                        $pos,
                        (new Definition(DatabaseConnection::class))
                            ->setFactory([new Reference(DatabaseConnectionManager::class), 'get'])
                            ->setArgument(0, $connectionName)
                    );
                }
            }
        }
    }
}
