<?php

namespace Arakne\Spinneret\Application\Config;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Util\Files;
use Closure;
use LogicException;
use Override;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;

use function array_push;
use function count;
use function glob;
use function implode;
use function is_file;
use function is_object;
use function is_subclass_of;
use function natsort;
use function realpath;
use function sprintf;

/**
 * Default config loader, using glob to load all *.php files from the config directory
 *
 * Handles two types of configuration files:
 * - a simple file which directly returns the configuration object (e.g. `<?php return new MyConfig();`)
 * - a closure which takes the previous configuration object as parameter (e.g. `<?php return function (?MyConfig $config) { ... };`)
 *
 * A cache file is created when dev mode is disabled, to avoid perform glob and reflection on each request.
 */
final readonly class PhpConfigLoader implements ConfigLoaderInterface
{
    public function __construct(
        /**
         * Name of the cache file
         */
        private string $cacheFile = 'config.php',
    ) {}

    /**
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    #[Override]
    public function load(Application $app): array
    {
        if (!$app->isDev) {
            $config = $this->loadFromCache($app);

            if ($config !== null) {
                return $config;
            }
        }

        $configByClassName = [];
        $filesByClassName = [];
        $files = $this->configFiles($app);

        foreach ($files as $file) {
            /** @psalm-suppress UnresolvableInclude */
            $configObject = require $file;
            $closureMetadata = null;

            if ($configObject instanceof Closure) {
                $closureMetadata = $this->processConfigurationClosure($file, $configObject);
                /** @psalm-suppress MixedAssignment */
                $configObject = $closureMetadata->call($app, $configByClassName);
            }

            if (!is_object($configObject)) {
                continue;
            }

            $configByClassName[$configObject::class] = $configObject;

            if ($closureMetadata === null) {
                $filesByClassName[$configObject::class] = [[$file, null]];
            } else {
                $filesByClassName[$configObject::class][] = [$file, $closureMetadata];
            }
        }

        $this->createCache($app, $filesByClassName);

        return $configByClassName;
    }

    /**
     * @param Application $app
     * @return array<string>
     */
    private function configFiles(Application $app): array
    {
        $globalConfigFiles = glob($app->configDir().'/*.php');
        natsort($globalConfigFiles);

        $currentEnvConfigFiles = glob($app->configDir().'/'.$app->env.'/*.php');
        natsort($currentEnvConfigFiles);

        array_push($globalConfigFiles, ...$currentEnvConfigFiles);

        return $globalConfigFiles;
    }

    private function processConfigurationClosure(string $file, Closure $config): ClosureMetadata
    {
        $reflectionFunction = new ReflectionFunction($config);
        $reflectionParameters = $reflectionFunction->getParameters();

        if (!$reflectionParameters) {
            return new ClosureMetadata($file, $config, [], null);
        }

        if (count($reflectionParameters) > 2) {
            throw new LogicException(sprintf('Invalid config file %s : the closure can take at most the application and the previous config object.', $file));
        }

        $parameters = [];
        $expectedConfigType = null;

        foreach ($reflectionParameters as $parameter) {
            $parameterType = $parameter->getType();

            if (!$parameterType instanceof ReflectionNamedType || $parameterType->isBuiltin()) {
                throw new LogicException(sprintf('Invalid type for parameter %s of config file %s : it must be an atomic nullable class.', $parameter->getName(), $file));
            }

            $parameterTypeName = $parameterType->getName();

            if ($parameterTypeName === Application::class || is_subclass_of($parameterTypeName, Application::class)) {
                $parameters[] = ClosureMetadata::PARAM_IS_APPLICATION;
            } else {
                $parameters[] = ClosureMetadata::PARAM_IS_CONFIG;
                $expectedConfigType = $parameterTypeName;
            }
        }

        return new ClosureMetadata($file, $config, $parameters, $expectedConfigType);
    }

    /**
     * @param Application $app
     * @return class-string-map<T, T>|null
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    private function loadFromCache(Application $app): ?array
    {
        $cacheFile = $app->cacheDir() . '/' . $this->cacheFile;

        if (!is_file($cacheFile)) {
            return null;
        }

        try {
            $config = require $cacheFile;
        } catch (Throwable) {
            return null;
        }

        if (!$config instanceof Closure) {
            return null;
        }

        return $config($app);
    }

    /**
     * @param Application $app
     * @param array<string, list<list{string, ClosureMetadata|null}>> $filesByClassName
     *
     * @return void
     */
    private function createCache(Application $app, array $filesByClassName): void
    {
        $lines = '';
        $configDir = realpath($app->configDir());

        foreach ($filesByClassName as $className => $files) {
            $callStack = 'null';

            foreach ($files as [$file, $closureMetadata]) {
                $file = str_replace($configDir, '', realpath($file));
                $req = 'require $configPath . ' . var_export($file, true);

                if ($closureMetadata === null) {
                    $callStack = $req;
                    continue;
                }

                $parameters = [];

                foreach ($closureMetadata->parameters as $parameter) {
                    $parameters[] = match ($parameter) {
                        ClosureMetadata::PARAM_IS_APPLICATION => '$app',
                        ClosureMetadata::PARAM_IS_CONFIG => $callStack,
                    };
                }

                $parameters = implode(', ', $parameters);
                $callStack = "($req)($parameters)";
            }

            $lines .= "\t\t" . var_export($className, true) . ' => ' . $callStack . ",\n";
        }

        $content = <<<PHP
            <?php

            return static function (Arakne\Spinneret\Application\Application \$app): array {
                \$configPath = \$app->configDir();

                return [
            $lines
                ];
            };
            PHP;

        Files::write($app->cacheDir().'/'.$this->cacheFile, $content);
    }
}

/**
 * @internal
 */
final readonly class ClosureMetadata
{
    public const int PARAM_IS_APPLICATION = 1;
    public const int PARAM_IS_CONFIG = 2;

    public function __construct(
        public string $file,
        public Closure $closure,

        /**
         * @var list<self::PARAM_IS_APPLICATION|self::PARAM_IS_CONFIG>
         */
        public array $parameters,
        public ?string $expectedReturnType = null,
    ) {}

    /**
     * @param Application $app
     * @param array<string, object> $previousConfig
     *
     * @return object|null
     */
    public function call(Application $app, array $previousConfig): ?object
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            $parameters[] = match ($parameter) {
                self::PARAM_IS_APPLICATION => $app,
                self::PARAM_IS_CONFIG => $previousConfig[$this->expectedReturnType] ?? null,
            };
        }

        $configObject = ($this->closure)(...$parameters);

        if (!is_object($configObject)) {
            return null;
        }

        if ($this->expectedReturnType !== null && !$configObject instanceof $this->expectedReturnType) {
            throw new LogicException(sprintf(
                'Invalid parameter for config file %s : the parameter type %s must be same as return type %s.',
                $this->file,
                $configObject::class,
                $this->expectedReturnType,
            ));
        }

        return $configObject;
    }
}
