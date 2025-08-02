<?php

namespace Arakne\Spinneret\Database\Argument;

use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Database\DatabaseConnectionInterface;
use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Override;
use Psr\Container\ContainerInterface;
use UnitEnum;

use function sprintf;
use function var_export;

// @todo test + doc
final readonly class DatabaseConnection implements ValueInterface
{
    public function __construct(
        /**
         * The name of the connection to use
         */
        public string|UnitEnum $name,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): DatabaseConnectionInterface
    {
        /** @var DatabaseConnectionManagerInterface $manager */
        $manager = $container->get(DatabaseConnectionManagerInterface::class);

        return $manager->get($this->name);
    }

    #[Override]
    public function compile(): string
    {
        return sprintf(
            '$this->get(%s)->get(%s)',
            var_export(DatabaseConnectionManagerInterface::class, true),
            Literal::dump($this->name),
        );
    }

    #[Override]
    public function type(): ?string
    {
        return DatabaseConnectionInterface::class;
    }
}
