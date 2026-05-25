<?php

namespace Arakne\Spinneret\Form\Transformer;

use Attribute;
use Override;
use Quatrevieux\Form\Transformer\Field\FieldTransformerInterface;
use Quatrevieux\Form\Transformer\Generator\FieldTransformerGeneratorInterface;
use Quatrevieux\Form\Transformer\Generator\FormTransformerGenerator;
use Quatrevieux\Form\Util\Code;

/**
 * Convert empty strings to null, so string validators like Length can be skipped when the field is empty.
 *
 * @implements FieldTransformerGeneratorInterface<EmptyStringToNull>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class EmptyStringToNull implements FieldTransformerInterface, FieldTransformerGeneratorInterface
{
    #[Override]
    public function transformFromHttp(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }

    #[Override]
    public function transformToHttp(mixed $value): mixed
    {
        return $value;
    }

    #[Override]
    public function canThrowError(): bool
    {
        return false;
    }

    #[Override]
    public function generateTransformFromHttp(object $transformer, string $previousExpression, FormTransformerGenerator $generator): string
    {
        return (string) Code::expr($previousExpression)->storeAndFormat("({} === '' ? null : {})");
    }

    #[Override]
    public function generateTransformToHttp(object $transformer, string $previousExpression, FormTransformerGenerator $generator): string
    {
        return $previousExpression;
    }
}
