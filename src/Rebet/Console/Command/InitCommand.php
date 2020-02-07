<?php
namespace Rebet\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Init Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class InitCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize a new Rebet application')
            // ->addArgument('name', InputArgument::OPTIONAL)
            // ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            // ->addOption('auth', null, InputOption::VALUE_NONE, 'Installs the Laravel authentication scaffolding')
            // ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists')
            ;
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
        return 0;
    }
}
