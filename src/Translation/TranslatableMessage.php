<?php

namespace Arakne\Spinneret\Translation;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Simple implementation of {@see TranslatableInterface} to wrap a message with its parameters
 */
final readonly class TranslatableMessage implements TranslatableInterface
{
    public function __construct(
        public string $message,

        /**
         * Replacement parameters
         *
         * @var array<string, mixed>
         */
        public array $parameters = [],
    ) {}

    #[Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->message, $this->parameters, locale: $locale);
    }
}
