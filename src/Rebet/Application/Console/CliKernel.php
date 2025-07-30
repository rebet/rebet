<?php
namespace Rebet\Application\Console;

use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Application\Kernel as ApplicationKernel;
use Rebet\Application\Structure;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * CLI (Command Line Interface) Kernel Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class CliKernel extends ApplicationKernel
{
    /**
     * Current handling input
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * Current handling output
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Status code of handling result
     *
     * @var int|null
     */
    protected $result;

    /**
     * {@inheritDoc}
     *
     * @param Structure $structure
     * @param string $channel (default: 'cli')
     */
    public function __construct(Structure $structure, string $channel = 'cli')
    {
        parent::__construct($structure, $channel);
    }

    /**
     * {@inheritDoc}
     */
    protected function bootstrappers() : array
    {
        return [
            LoadEnvironmentVariables::class,
            [PropertiesMaskingConfiguration::class, 'masks' => ['password', 'password_confirm']],
            LoadApplicationConfiguration::class,
            HandleExceptions::class,
            LetterpressTagCustomizer::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface $input (default: null)
     * @param OutputInterface $output (default: null)
     * @return int
     */
    public function handle($input = null, $output = null)
    {
        return $this->result = $this->assistant()->run(
            $this->input  = $input ?? new ArgvInput(),
            $this->output = $output ?? new ConsoleOutput()
        );
    }

    /**
     * Run a command by name.
     *
     * @param string $action
     * @param array $parameters (default: [])
     * @param OutputInterface $output (default: null)
     * @return int
     */
    public function call(string $action, array $parameters = [], $output = null)
    {
        return $this->result = $this->assistant()->run(
            $this->input  = new ArrayInput(array_merge($parameters, ['command' => $action])),
            $this->output = $output ?? new ConsoleOutput()
        );
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate() : void
    {
        // Currently nothing to do.
    }

    /**
     * {@inheritDoc}
     */
    public function fallback(\Throwable $e) : int
    {
        return $this->result = $this->exceptionHandler()->handle($this->input ?? $this->input = new ArgvInput(), $this->output, $e);
    }

    /**
     * {@inheritDoc}
     */
    public function report(\Throwable $e) : void
    {
        $this->exceptionHandler()->report($this->input ?? $this->input = new ArgvInput(), $this->result, $e);
    }
}
