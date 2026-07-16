<?php

namespace Arakne\Spinneret\Scheduler\Console;

use Arakne\Spinneret\Scheduler\Locator\ScheduledTaskRegistry;
use Arakne\Spinneret\Scheduler\ScheduledTaskInterface;
use Arakne\Spinneret\Scheduler\Scheduler;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;
use function sprintf;

#[AsCommand(
    name: 'scheduler:start',
    description: 'Starts the scheduler worker.',
)]
final class StartSchedulerCommand extends Command
{
    public function __construct(
        private readonly ScheduledTaskRegistry $taskRegistry,
        private readonly ?LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'Timeout in milliseconds for the scheduler to run before exiting.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tasks = $this->taskRegistry->tasks();

        if (!$tasks) {
            $io->warning('No scheduled tasks found.');
            return self::SUCCESS;
        }

        $io->info(sprintf('Starting scheduler with %d task(s):', count($tasks)));
        $io->listing(array_map(
            static fn(ScheduledTaskInterface $task) => sprintf(
                '%s (%s %s)',
                $task->name(),
                $task->perpetual() ? 'every' : 'in',
                $task->delay(),
            ),
            $tasks
        ));

        $scheduler = new Scheduler($this->logger);  // @todo Decorate with "console" logger

        foreach ($tasks as $task) {
            $scheduler->add($task);
        }

        $scheduler->start(
            // @phpstan-ignore cast.int
            $input->getOption('timeout') ? (int) $input->getOption('timeout') : null
        );

        return self::SUCCESS;
    }
}
