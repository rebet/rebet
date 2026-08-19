<?php
namespace Rebet\Application\Console;

use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Application\Kernel;
use Rebet\Application\Structure;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI (Command Line Interface) Kernel Class
 *
 * @template-extends Kernel<InputInterface, int>
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class CliKernel extends Kernel
{
    /**
     * Current handling input
     *
     * @var InputInterface|null
     */
    protected InputInterface|null $input;

    /**
     * Current handling output
     *
     * @var OutputInterface|null
     */
    protected OutputInterface|null $output;

    /**
     * Rebet assistant console application.
     *
     * @var Assistant|null
     */
    protected Assistant|null $assistant;

    /**
     * Status code of handling result
     *
     * @var int|null
     */
    protected int|null $result;

    /**
     * {@inheritDoc}
     *
     * @param Structure $structure
     * @param string $channel (default: 'cli')
     * @param OutputInterface|null $output (default: null for ConsoleOutput())
     */
    public function __construct(Structure $structure, string $channel = 'cli', OutputInterface|null $output = null)
    {
        parent::__construct($structure, $channel);
        $this->output = $output ?? new ConsoleOutput();
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
     * Get rebet assistant console application.
     *
     * @return Assistant
     */
    public function assistant() : Assistant
    {
        return $this->assistant;
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function bootstrap() : void
    {
        parent::bootstrap();
        $this->assistant = new Assistant();
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface|null $input (default: null for ArgvInput())
     * @return int
     */
    public function handle($input = null) : int
    {
        return $this->result = $this->assistant()->run(
            $this->input = $input ?? new ArgvInput(),
            $this->output
        );
    }

    /**
     * Run a command by name.
     *
     * @param string $action
     * @param array $parameters (default: [])
     * @return int
     */
    public function call(string $action, array $parameters = []) : int
    {
        return $this->result = $this->assistant()->run(
            $this->input = new ArrayInput(array_merge($parameters, ['command' => $action])),
            $this->output
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
