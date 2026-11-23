<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Install\DbInstall;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'app:install',
    'Install the database',
    aliases: ['install'],
    help: 'Creates and updates the database schema from config/migrations. Use the --fresh option to drop all tables first.',
)]
class InstallCommand extends Command
{
    protected function configure(): void {
        $this->addOption('fresh', 'f', InputOption::VALUE_NONE, 'Drop all tables and install from scratch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        return DbInstall::install((bool) $input->getOption('fresh'), $output) ? self::SUCCESS : self::FAILURE;
    }
}
