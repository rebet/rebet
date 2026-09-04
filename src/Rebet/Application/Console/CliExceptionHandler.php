<?php
declare(strict_types=1);

namespace Rebet\Application\Console;

use Rebet\Application\ExceptionHandler;
use Rebet\Log\Log;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Exception Handler Class
 *
 * @template-extends ExceptionHandler<InputInterface, int>
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class CliExceptionHandler extends ExceptionHandler
{
    /**
     * Current handling output
     *
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * {@inheritDoc}
     * @param OutputInterface|null $output (default: null) If null, new ConsoleOutput() will be used.
     */
    public function __construct($output = null)
    {
        $this->output = $output ?? new ConsoleOutput();
    }

    /**
     * Report an exception.
     * Just only report, this function do not display result.
     *
     * @param InputInterface $input
     * @param int|null $result
     * @param \Throwable $e
     * @return void
     */
    public function report($input, $result, \Throwable $e) : void
    {
        Log::error("Console unhandled exception occurred. Error code: {$result}", ['arguments' => $input->getArguments(), 'options' => $input->getOptions()], $e);
    }

    /**
     * Handle an exception
     *
     * @param InputInterface|null $input
     * @param \Throwable $e
     * @return int
     */
    public function handle($input, \Throwable $e)
    {
        $input  = $input ?? new ArgvInput();
        $status = 1;
        $this->report($input, $status, $e);
        $this->output->writeln('<error>********************************************</error>');
        $this->output->writeln('<error>*   Console Unhandled Exception Occurred   *</error>');
        $this->output->writeln('<error>********************************************</error>');
        $this->output->writeln('<comment>'.$e->getMessage().'</comment>');
        $this->output->writeln('Exception:');
        $this->output->writeln($e->getTraceAsString());
        return $status;
    }
}
