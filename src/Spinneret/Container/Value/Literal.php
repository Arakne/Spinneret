<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Attribute;
use DateTimeZone;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use stdClass;
use UnitEnum;

use function array_is_list;
use function array_map;
use function gettype;
use function implode;
use function is_array;
use function is_object;
use function is_scalar;
use function sprintf;
use function var_export;

/**
 * Represents a raw value as an argument.
 * The value will be used as-is when resolving the argument.
 *
 * Note: if the value is an object, it must have a public constructor, and use only promoted properties.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Literal implements ValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        public mixed $value,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        return $this->value;
    }

    #[Override]
    public function compile(): string
    {
        return self::dump($this->value);
    }

    #[Override]
    public function type(): ?string
    {
        return match (gettype($this->value)) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'string' => 'string',
            'array' => 'array',
            'object' => $this->value::class,
            'NULL' => 'null',
            default => null,
        };
    }

    /**
     * Dump the given value to a PHP code representation.
     *
     * Example:
     * ```
     * assert(42 === eval(Literal::dump(42)));
     * assert('foo' === eval(Literal::dump('foo')));
     * assert([1, 2, 3] === eval(Literal::dump([1, 2, 3])));
     * assert(new MyObject('foo', 'bar') == eval(Literal::dump(new MyObject('foo', 'bar'))));
     * ```
     *
     * @param mixed $value
     * @return string
     *
     * @throws ContainerBuildException When the value is of an unsupported type.
     */
    public static function dump(mixed $value): string
    {
        if (is_scalar($value)) {
            return var_export($value, true);
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return self::dumpArray($value);
        }

        if (is_object($value)) {
            return self::dumpObject($value);
        }

        throw new ContainerBuildException(sprintf('Unsupported value type: %s', gettype($value)));
    }

    /**
     * @param mixed[] $value
     * @return string
     */
    private static function dumpArray(array $value): string
    {
        $isList = array_is_list($value);
        $output = '[';

        /** @var mixed $item */
        foreach ($value as $key => $item) {
            $item = self::dump($item);

            if ($isList) {
                $output .= $item . ', ';
            } else {
                $output .= var_export($key, true) . ' => ' . $item . ', ';
            }
        }

        return $output . ']';
    }

    private static function dumpObject(object $obj): string
    {
        if ($obj instanceof stdClass) {
            return sprintf('((object) %s)', self::dump((array)$obj));
        }

        if ($obj instanceof UnitEnum) {
            return sprintf('\%s::%s', $obj::class, $obj->name);
        }

        if ($obj instanceof DateTimeZone) {
            return sprintf('new \%s(%s)', $obj::class, var_export($obj->getName(), true));
        }

        $reflection = new ReflectionObject($obj);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return sprintf('new \%s()', $reflection->getName());
        }

        if (!$constructor->isPublic()) {
            throw new ContainerBuildException(sprintf(
                'Cannot dump object of class %s: constructor is not public.',
                $reflection->getName()
            ));
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isPromoted()) {
                /** @var mixed */
                $arguments[] = $reflection->getProperty($parameter->name)->getValue($obj);
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            if ($parameter->isOptional()) {
                break;
            }

            throw new ContainerBuildException(sprintf(
                'Cannot dump object of class %s: only optional or promoted parameters are supported.',
                $reflection->getName(),
            ));
        }

        return sprintf(
            'new \%s(%s)',
            $reflection->getName(),
            implode(', ', array_map(self::dump(...), $arguments)),
        );
    }
}
