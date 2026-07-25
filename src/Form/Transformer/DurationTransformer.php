<?php

namespace Arakne\Spinneret\Form\Transformer;

use Attribute;
use DateInterval;
use Override;
use Quatrevieux\Form\Transformer\Field\FieldTransformerInterface;

use function is_string;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class DurationTransformer implements FieldTransformerInterface
{
    #[Override]
    public function transformFromHttp(mixed $value): ?DateInterval
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return new DateInterval($value);
    }

    #[Override]
    public function transformToHttp(mixed $value): ?string
    {
        if (!$value instanceof DateInterval) {
            return null;
        }

        $out = 'P';

        if ($value->y > 0) {
            $out .= $value->y . 'Y';
        }

        if ($value->m > 0) {
            $out .= $value->m . 'M';
        }

        if ($value->d > 0) {
            $out .= $value->d . 'D';
        }

        if ($value->h > 0 || $value->i > 0 || $value->s > 0) {
            $out .= 'T';

            if ($value->h > 0) {
                $out .= $value->h . 'H';
            }

            if ($value->i > 0) {
                $out .= $value->i . 'M';
            }

            if ($value->s > 0) {
                $out .= $value->s . 'S';
            }
        }

        return $out;
    }

    #[Override]
    public function canThrowError(): bool
    {
        return true;
    }
}
