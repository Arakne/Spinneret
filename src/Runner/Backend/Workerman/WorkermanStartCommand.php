<?php

namespace Arakne\Spinneret\Runner\Backend\Workerman;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'workerman:start',
    description: 'Start the Workerman server'
)]
final class WorkermanStartCommand extends Command
{
    public function __construct(
        private readonly WorkermanBackend $backend,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting Workerman server');

        if (!$this->backend->checkJit()) {
            $io->warning('JIT is not enabled, consider enabling it for better performance');
        }

        $this->backend->start();

        return Command::SUCCESS;
    }
}
