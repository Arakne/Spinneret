<?php

namespace Arakne\Spinneret\Container\Compiler;

final class CompiledPhpClassContainer
{
    public string $className {
        get => ($this->namespace !== '' ? $this->namespace . '\\' : '') . $this->simpleClassName;
    }

    public function __construct(
        /**
         * The class namespace.
         * Use empty string for default namespace
         */
        public readonly string $namespace,

        /**
         * The class basename/simple name (i.e. name without namespace)
         */
        public readonly string $simpleClassName,

        /**
         * The class file content.
         * Note: do not contain the php open tag
         */
        public readonly string $body,

        /**
         * The hash of the body. This hash is computed without the class name.
         */
        public readonly string $hash,
    ) {}
}
