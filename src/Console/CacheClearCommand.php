<?php

namespace Arakne\Spinneret\Console;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Util\Files;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function is_dir;

#[AsCommand(
    name: 'cache:clear',
    description: 'Clears the cache',
    aliases: ['clear:cache'],
)]
final class CacheClearCommand extends Command
{
    public function __construct(
        private readonly Application $application,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $dir = $this->application->cacheDir();

        if (!is_dir($dir)) {
            $style->warning('Cache directory does not exist.');
            return self::SUCCESS;
        }

        Files::rmdir($this->application->cacheDir());
        $style->success('Cache cleared.');

        return self::SUCCESS;
    }
}
