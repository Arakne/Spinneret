<?php

namespace Arakne\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Exception\MissingArgumentException;
use Override;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * Explicitly announces that the argument should be autowired.
 * Autowired argument cannot be resolved nor compiled, it must be replaced by a processor.
 */
final readonly class Autowire implements ArgumentInterface
{
    public function __construct(
        /**
         * The service ID that requires this argument.
         * Only used for error reporting.
         */
        public ?string $serviceId = null,

        /**
         * The name of the current parameter.
         * Only used for error reporting.
         */
        public ?string $parameterName = null,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): never
    {
        $this->error();
    }

    #[Override]
    public function compile(): string
    {
        $this->error();
    }

    private function error(): never
    {
        $message = sprintf(
            'Autowire argument cannot be resolved. Please provide a service ID or a parameter name. Given: serviceId=%s, parameterName=%s',
            $this->serviceId ?? 'null',
            $this->parameterName ?? 'null'
        );

        throw new MissingArgumentException($message);
    }

    #[Override]
    public function type(): ?string
    {
        return null;
    }
}
