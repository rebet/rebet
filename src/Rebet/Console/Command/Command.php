<?php
namespace Rebet\Console\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Command Class
 *
 * @todo https://github.com/laravel/framework/blob/7.x/src/Illuminate/Console/Concerns/InteractsWithIO.php
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Command extends SymfonyCommand
{
    /**
     * The name of command.
     * Must be overloaded in subclass.
     * @var string
     */
    const NAME = null;

    /**
     * The description of command.
     * Must be overloaded in subclass.
     * @var string
     */
    const DESCRIPTION = null;

    /**
     * The arguments of command.
     * Must be overloaded in subclass if necessary.
     * @var array
     */
    const ARGUMENTS = [];

    /**
     * The options of command.
     * Must be overloaded in subclass if necessary.
     * @var array
     */
    const OPTIONS = [];

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
     *
     * @var array
     */
    const VERBOSITIES = [
        'q'   => OutputInterface::VERBOSITY_QUIET,
        ''    => OutputInterface::VERBOSITY_NORMAL,
        'v'   => OutputInterface::VERBOSITY_VERBOSE,
        'vv'  => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
    ];

    /**
     * The input interface implementation.
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * The default verbosity of this command.
     *
     * @var int (default: OutputInterface::VERBOSITY_NORMAL)
     */
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * Question Helper of this command
     *
     * @var QuestionHelper
     */
    protected $questionner;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(static::NAME);
        $this->setDescription(static::DESCRIPTION);
        foreach (static::ARGUMENTS as $argment) {
            $this->addArgument(...$argment);
        }
        foreach (static::OPTIONS as $option) {
            $this->addOption(...$option);
        }
        $this->setHelperSet(new HelperSet([new QuestionHelper()]));
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
        $this->input       = $input;
        $this->output      = $output;
        $this->questionner = $this->getHelper('question');
        return $this->handle() ?? 0 ;
    }

    /**
     * Handle command process
     *
     * @return void|null|int status code (when void or null then 0)
     */
    abstract protected function handle();

    /**
     * Determine if the given argument is present.
     *
     * @param string|int $key
     * @return bool
     */
    public function hasArgument($key) : bool
    {
        return $this->input->hasArgument($key);
    }

    /**
     * Get the value of a command argument.
     *
     * @param string $key
     * @return string|null
     */
    public function argument(string $key) : ?string
    {
        return $this->input->getArgument($key) ;
    }

    /**
     * Get all of the arguments passed to the command.
     *
     * @return array
     */
    public function arguments() : array
    {
        return $this->input->getArguments();
    }

    /**
     * Determine if the given option is present.
     *
     * @param string $key
     * @return bool
     */
    public function hasOption(string $key) : bool
    {
        return $this->input->hasOption($key);
    }

    /**
     * Get the value of a command option.
     *
     * @param string $key
     * @return string|bool|null
     */
    public function option(string $key)
    {
        return $this->input->getOption($key);
    }

    /**
     * Get all of the options passed to the command.
     *
     * @return array
     */
    public function options() : array
    {
        return $this->input->getOptions();
    }

    /**
     * Ask the given question.
     *
     * @param Question $question
     * @return mixed
     */
    protected function _ask(Question $question)
    {
        return $this->questionner->ask($this->input, $this->output, $question);
    }

    /**
     * Confirm a question with the user.
     *
     * @param string $question
     * @param bool $default (default: false)
     * @return bool
     */
    protected function confirm(string $question, bool $default = false) : bool
    {
        return !$this->_ask(new ConfirmationQuestion($question, $default));
    }

    /**
     * Prompt the user for input.
     *
     * @param string $question
     * @param string|null $default (default: null)
     * @param array|callable|null $choices for auto completion. (default: null)
     * @return mixed
     */
    protected function ask(string $question, ?string $default = null, $choices = null)
    {
        $question = new Question($question, $default);
        if ($choices !== null) {
            is_callable($choices) ? $question->setAutocompleterCallback($choices) : $question->setAutocompleterValues($choices);
        }
        return $this->_ask($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param string $question
     * @param bool $fallback (default: true)
     * @return mixed
     */
    protected function secret(string $question, bool $fallback = true)
    {
        $question = new Question($question);
        return $this->_ask($question->setHidden(true)->setHiddenFallback($fallback));
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default (default: null)
     * @param int|null $attempts (default: null)
     * @param bool $multiple (default: false)
     * @return string|array
     */
    protected function choice(string $question, array $choices, $default = null, ?int $attempts = null, $multiple = false)
    {
        $question = new ChoiceQuestion($question, $choices, $default);
        return $this->_ask($question->setMaxAttempts($attempts)->setMultiselect($multiple));
    }

    /**
     * Format input to textual table.
     *
     * @param array $headers
     * @param array $rows
     * @param string $table_style (default: 'default')
     * @param array $column_styles (default: [])
     * @return void
     */
    protected function table(array $headers, array $rows, $table_style = 'default', array $column_styles = [])
    {
        $table = new Table($this->output);
        $table->setHeaders($headers)->setRows($rows)->setStyle($table_style);
        foreach ($column_styles as $col => $style) {
            $table->setColumnStyle($col, $style);
        }
        $table->render();
    }

    /**
     * Get/Set the verbosity level.
     *
     * @param  string|int  $level (default: null)
     * @return self|int
     */
    protected function verbosity($level = null)
    {
        if ($level === null) {
            return $this->verbosity;
        }
        $this->verbosity = $this->parseVerbosity($level);
        return $this;
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param  string|int|null  $level
     * @return int
     */
    protected function parseVerbosity($level) : int
    {
        return self::VERBOSITIES[$level] ?? $level ?? $this->verbosity ;
    }

    /**
     * Write a message as standard output.
     *
     * @param string $message
     * @param int|string|null $verbosity (default: null)
     * @return self
     */
    protected function write($message, $verbosity = null)
    {
        $this->output->write($message, false, $this->parseVerbosity($verbosity));
        return $this;
    }

    /**
     * Write a message with newline as standard output.
     *
     * @param string $message
     * @param int|string|null $verbosity (default: null)
     * @return self
     */
    protected function writeln($message, $verbosity = null)
    {
        $this->output->writeln($message, $this->parseVerbosity($verbosity));
        return $this;
    }

    /**
     * Write a string as information output.
     *
     * @param string $message
     * @param int|string|null $verbosity
     * @return self
     */
    protected function info($message, $verbosity = null)
    {
        return $this->writeln("<info>{$message}</info>", $verbosity);
    }

    /**
     * Write a string as comment output.
     *
     * @param string $message
     * @param int|string|null $verbosity
     * @return self
     */
    protected function comment($message, $verbosity = null)
    {
        return $this->writeln("<comment>{$message}</comment>", $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param string $message
     * @param int|string|null $verbosity
     * @return self
     */
    protected function question($message, $verbosity = null)
    {
        return $this->writeln("<question>{$message}</question>", $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param string $message
     * @param int|string|null $verbosity
     * @return self
     */
    protected function error($message, $verbosity = null)
    {
        return $this->writeln("<error>{$message}</error>", $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param string $message
     * @param int|string|null $verbosity
     * @return self
     */
    protected function warning($message, $verbosity = null)
    {
        if (!$this->output->getFormatter()->hasStyle('warning')) {
            $this->output->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow'));
        }
        return $this->writeln("<warning>{$message}</warning>", $verbosity);
    }
}
