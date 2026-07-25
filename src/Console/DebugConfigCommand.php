<?php

namespace Arakne\Spinneret\Console;

use Arakne\Spinneret\Application\Application;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_is_list;
use function array_map;
use function implode;
use function is_array;
use function is_object;
use function stripos;
use function var_export;

#[AsCommand(
    name: 'debug:config',
    description: 'Display the configuration',
)]
final class DebugConfigCommand extends Command
{
    public function __construct(
        private readonly Application $application,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('filter', InputArgument::OPTIONAL, 'Filter the configuration by a specific class');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        // @phpstan-ignore cast.string
        $filter = (string) $input->getArgument('filter');

        /**
         * @var object $config
         */
        foreach ($this->application->config() as $config) {
            $class = $config::class;

            if ($filter !== '' && stripos($class, $filter) === false) {
                continue;
            }

            $style->section($class);
            $style->table(
                ['Key', 'Value'],
                $this->dumpConfigValues($config)
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param object $config
     * @return list<list{array-key, string}>
     */
    private function dumpConfigValues(object $config): array
    {
        $values = [];

        /**
         * @var mixed $value
         */
        foreach ((array) $config as $key => $value) {
            $values[] = [$key, $this->dumpValue($value)];
        }

        return $values;
    }

    private function dumpValue(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '[' . implode(', ', array_map($this->dumpValue(...), $value)) . ']';
            }

            return "[\n" . implode(",\n", array_map(fn($key, $value) => '  ' . (string) $key . ' => ' . $this->dumpValue($value), array_keys($value), $value)) . "\n]";
        }

        if (is_object($value)) {
            $arrValue = (array) $value;
            return $value::class . " {\n" . implode(",\n", array_map(fn($key, $value) => '  ' . (string) $key . ' = ' . $this->dumpValue($value), array_keys($arrValue), $arrValue)) . "\n}";
        }

        return var_export($value, true);
    }
}
