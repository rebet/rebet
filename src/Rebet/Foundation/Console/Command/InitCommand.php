<?php
namespace Rebet\Foundation\Console\Command;

use Rebet\Common\Path;
use Rebet\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

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
    const NAME        = 'init';
    const DESCRIPTION = 'Initialize a new Rebet application';

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @todo https://symfony.com/doc/current/console.html
        // @todo https://symfony.com/doc/current/components/console/helpers/questionhelper.html
        // @todo https://techblog.istyle.co.jp/archives/97
        // @todo https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/EnvironmentCommand.php

        $helper   = $this->getHelper('question');
        $cwd      = Path::normalize(getcwd());

        // $question = new ConfirmationQuestion('Continue with this action?', false);
        // if (!$helper->ask($input, $output, $question)) {
        //     return 0;
        // }

        // $question   = new Question('Please enter the name of the bundle', 'AcmeDemoBundle');
        // $bundleName = $helper->ask($input, $output, $question);
        // echo($bundleName);

        // $question = new ChoiceQuestion(
        //     'Please select your favorite color (defaults to red)',
        //     ['red', 'blue', 'yellow'],
        //     0
        // );
        // $question->setErrorMessage('Color %s is invalid.');
        // $color = $helper->ask($input, $output, $question);


        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
        return 0;
    }
}
