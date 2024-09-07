<?php

namespace Arakne\Spinneret\Form\Csrf;

use function hash_equals;
use function hash_hmac;

/**
 * Parameters for CSRF token generation and validation
 */
final readonly class CsrfTokenParameters
{
    public function __construct(
        private ?string $key,
        private ?string $secret,
        private ?string $input,
    ) {
    }

    public function token(): ?string
    {
        if ($this->key === null || $this->secret === null) {
            return null;
        }

        return hash_hmac('sha256', $this->key, $this->secret);
    }

    public function validate(): bool
    {
        $token = $this->token();
        $input = $this->input;

        return $token !== null && $input !== null && hash_equals($token, $input);
    }
}
