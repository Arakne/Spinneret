<?php

namespace Arakne\Spinneret\View;

use Stringable;

use function htmlentities;
use function is_float;
use function is_int;

/**
 * Escape a value for HTML output.
 * Unlike {@see htmlspecialchars}, this function handles non-string values.
 *
 * @param int|string|float|null|Stringable $value Value to escape
 * @return string Escaped value
 */
function e(int|string|float|Stringable|null $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return htmlentities((string) $value);
}
