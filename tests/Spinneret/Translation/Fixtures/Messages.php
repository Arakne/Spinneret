<?php

namespace Arakne\Tests\Spinneret\Translation\Fixtures;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class Messages
{
    public function __construct(
        public TranslatorInterface $translator,
    ) {
    }

    public function show(?string $locale = null): array
    {
        return [
            $this->translator->trans('Hello, World!', locale: $locale),
            $this->translator->trans('Hello, {name}!', ['{name}' => 'John'], locale: $locale),
            $this->translator->trans('Missing translation', locale: $locale),
        ];
    }
}
