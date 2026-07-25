<?php

namespace Arakne\Spinneret\Application\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Container\BuiltContainer;
use Arakne\Spinneret\Container\Compiler\PhpClassContainerCompiler;
use Arakne\Spinneret\Container\SpinneretContainerInterface;
use Arakne\Spinneret\Util\Files;
use Override;
use Throwable;

use function bin2hex;
use function is_file;
use function preg_replace;
use function random_bytes;
use function var_dump;

/**
 * Default implementation of {@see ContainerCompilerInterface}.
 * Use {@see PhpDumper} to compile the container into multiple PHP files.
 */
final readonly class ContainerCompiler implements ContainerCompilerInterface
{
    public function __construct(
        /**
         * The save directory path, relative to the cache directory
         */
        private string $savePath = '',
    ) {}

    #[Override]
    public function load(Application $application): ?SpinneretContainerInterface
    {
        $containerClassPath = $application->cacheDir() . '/' . $this->savePath . '/' . $this->containerClassName($application) . '.php';

        if (!is_file($containerClassPath)) {
            return null;
        }

        try {
            $container = include $containerClassPath;
        } catch (Throwable) {
            return null;
        }

        if (!$container instanceof SpinneretContainerInterface) {
            return null;
        }

        return $container;
    }

    #[Override]
    public function compile(Application $application, BuiltContainer $container): void
    {
        $fileName = $this->containerClassName($application);
        $className = $fileName . '_' . bin2hex(random_bytes(8));
        $code = <<<PHP
            <?php

            require_once __DIR__ . '/{$className}.php';

            return new {$className}();
            PHP
        ;

        $classCode = '<?php ' . $container->compile(new PhpClassContainerCompiler($className));

        Files::write($application->cacheDir() . '/' . $this->savePath . '/' . $className . '.php', $classCode);
        Files::write($application->cacheDir() . '/' . $this->savePath . '/' . $fileName . '.php', $code);
    }

    private function containerClassName(Application $application): string
    {
        return preg_replace('/[^A-Z0-9_]/i', '_', $application::class) . 'Container';
    }
}
