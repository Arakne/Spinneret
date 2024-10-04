<?php

namespace Arakne\Spinneret\Database\Migration\Console;

use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Spinneret\Database\Migration\MigrationStatus;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db:migration:status',
    description: 'List all available migrations and their status',
)]
final class MigrationStatusCommand extends Command
{
    public function __construct(
        private readonly MigrationManager $migrationManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $style->info('Current version: ' . ($this->migrationManager->currentVersion() ?? 'None (No migration applied)'));

        $style->table(
            ['Name', 'Date', 'Version', 'Applied'],
            array_map(
                fn (MigrationStatus $migration) => [
                    $migration->migration->name(),
                    $migration->migration->date()->format('Y-m-d H:i:s'),
                    $migration->migration->version(),
                    $migration->applied ? '<fg=black;bg=green>Yes</>' : '<fg=white;bg=red>No</>',
                ],
                $this->migrationManager->list()
            )
        );

        return Command::SUCCESS;
    }
}
