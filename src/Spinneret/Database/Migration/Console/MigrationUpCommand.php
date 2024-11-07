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
    name: 'db:migration:up',
    description: 'Apply all pending migrations',
)]
final class MigrationUpCommand extends Command
{
    public function __construct(
        private readonly MigrationManager $migrationManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @todo up force migrations list
        $io = new SymfonyStyle($input, $output);

        if ($this->migrationManager->up(fn (string $line, bool $newLine = true) => $output->write($line, $newLine)) === 0) {
            $io->success('No pending migrations');
        } else {
            $io->success('Migrations applied');
        }

        return Command::SUCCESS;
    }
}
