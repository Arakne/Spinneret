<?php

namespace Arakne\Spinneret\Container\Argument;

use Override;
use Psr\Container\ContainerInterface;

use function is_array;
use function sprintf;

// @todo test + doc
final readonly class NewExpression implements ArgumentInterface
{
    public function __construct(
        public string $className,
        public array $arguments = [],
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        $arguments = [];

        foreach ($this->arguments as $key => $value) {
            if (is_array($value)) {
                $value = new DynamicArray($value);
            }

            if ($value instanceof ArgumentInterface) {
                /** @var mixed $value */
                $value = $value->resolve($container);
            }

            /** @var mixed $value */
            $arguments[$key] = $value;
        }

        return new ($this->className)(...$arguments);
    }

    #[Override]
    public function compile(): string
    {
        $isList = array_is_list($this->arguments);
        $output = '';

        /** @var mixed $value */
        foreach ($this->arguments as $key => $value) {
            if (is_array($value)) {
                $value = new DynamicArray($value);
            }

            if ($value instanceof ArgumentInterface) {
                $value = $value->compile();
            } else {
                $value = Literal::dump($value);
            }

            if ($isList) {
                $output .= $value . ', ';
            } else {
                $output .= $key . ': ' . $value . ', ';
            }
        }

        return sprintf('new \%s(%s)', $this->className, rtrim($output, ', ')); // @todo use implode instead
    }

    #[Override]
    public function type(): ?string
    {
        return $this->className;
    }
}
