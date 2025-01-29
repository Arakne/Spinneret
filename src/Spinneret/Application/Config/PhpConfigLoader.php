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
use function is_file;
use function is_object;
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
            $isClosure = false;

            if ($configObject instanceof Closure) {
                $isClosure = true;

                /** @psalm-suppress MixedAssignment */
                $configObject = $this->callConfigurationClosure($file, $configByClassName, $configObject);
            }

            if (!is_object($configObject)) {
                continue;
            }

            $configByClassName[$configObject::class] = $configObject;

            if (!$isClosure) {
                $filesByClassName[$configObject::class] = [[$file, false]];
            } else {
                $filesByClassName[$configObject::class][] = [$file, true];
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

    private function callConfigurationClosure(string $file, array $previousConfig, Closure $config): mixed
    {
        // @todo Permettre de passer l'application en 2e paramètre, donnant accès aux dossier de l'application
        $reflectionFunction = new ReflectionFunction($config);
        $parameters = $reflectionFunction->getParameters();

        if (count($parameters) > 1) {
            throw new LogicException(sprintf('Invalid config file %s : the closure must take at most one parameter.', $file));
        }

        if (!$parameters) {
            return $config();
        }

        $parameter = $parameters[0];
        $parameterType = $parameter->getType();

        if (!$parameterType instanceof ReflectionNamedType) {
            throw new LogicException(sprintf('Invalid type for parameter %s of config file %s : it must be an atomic nullable class.', $parameter->getName(), $file));
        }

        return $config($previousConfig[$parameterType->getName()] ?? null);
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
     * @param array<string, list<list{string, bool}>> $filesByClassName
     *
     * @return void
     */
    private function createCache(Application $app, array $filesByClassName): void
    {
        $lines = '';
        $configDir = realpath($app->configDir());

        foreach ($filesByClassName as $className => $files) {
            $callStack = 'null';

            foreach ($files as [$file, $isClosure]) {
                $file = str_replace($configDir, '', realpath($file));
                $req = 'require $configPath . ' . var_export($file, true);

                if (!$isClosure) {
                    $callStack = $req;
                    continue;
                }

                $callStack = "($req)($callStack)";
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
