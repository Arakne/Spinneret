<?php

namespace Arakne\Spinneret\Form\Csrf;

use SensitiveParameter;

use function hash_equals;
use function hash_hmac;

// @todo doc
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

        return $token !== null && hash_equals($this->token(), $this->input);
    }
}
