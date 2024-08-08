<?php

namespace Arakne\Spinneret\View;

final class View
{
    public function __construct(
        public readonly object $data,
    ) {
    }
}
