<?php

namespace Arakne\Spinneret\Runner;

final readonly class RunnerConfig
{
    public function __construct(
        /**
         * Enable the httpd backend
         * Should be true for run the application through a web server like Apache or Nginx, using FPM or mod_php
         *
         * Note: this value is resolved at compile time, so you should not use an environment variable here
         */
        public bool $httpd = true,
    ) {
    }
}
