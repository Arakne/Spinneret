<?php

namespace Arakne\Spinneret\Database\Migration\Console;

use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Spinneret\Database\Migration\MigrationStatus;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db:migration:down',
    description: 'Rollback migrations',
)]
final class MigrationDownCommand extends Command
{
    // @todo filter version
    public function __construct(
        private readonly MigrationManager $migrationManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption('until', 'u', InputOption::VALUE_REQUIRED, 'Rollback all migrations up to this version');
        $this->addArgument('migrations', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Migrations names to rollback');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Allow rollback of migrations that are not marked as applied');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $out = fn (string $line, bool $newLine = true) => $output->write($line, $newLine);

        /** @var string|null $version */
        $version = $input->getOption('until');
        /** @var list<string> $migrations */
        $migrations = $input->getArgument('migrations');
        $force = (bool) $input->getOption('force');

        if ($version === null && empty($migrations)) {
            $io->error('You must provide at least one migration name or a version to rollback');
            return Command::FAILURE;
        }

        $count = 0;

        if ($version !== null) {
            $count += $this->migrationManager->rollback($version, $force, $out);
        }

        if ($migrations) {
            $count += $this->migrationManager->down($migrations, $force, $out);
        }

        if ($count > 0) {
            $io->success('Migrations rolled back');
        } else {
            $io->warning('No migrations to rollback. Use --force option to allow rollback migrations that are not marked as applied.');
        }

        return Command::SUCCESS;
    }
}
