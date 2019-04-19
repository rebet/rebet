<?php
namespace Rebet\Log\Driver\Monolog;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\ProcessorInterface;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;

/**
 * Monolog Driver Class
 *
 * This class will set builtin processors defined in configure.
 * In liblary default defined bultin processors below,
 *   - IntrospectionProcessor
 *   - ProcessIdProcessor
 *
 * Usage: (Parameter of Constractor)
 *   'driver'     [*] MonologDriver::class,
 *   'name'       [*] string of name (usualy same as channel name),
 *   'level'      [*] string of LogLevel::*,
 *   'handlers'   [ ] HandlerInterface[] for optional stack of handlers, the first one in the array is called first, etc. (default: [])
 *   'processors' [ ] array of callable processors. (default: [])
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MonologDriver extends MonologLogger
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'builtin_processors' => [
                ProcessIdProcessor::class,
            ],
            'processors' => [
                ProcessIdProcessor::class => [],
            ],
            'formatters' => [
                TextFormatter::class => [
                    'format' => "{datetime} {channel}/{extra.process_id} [{level_name}] {message}{context}{extra}{exception}\n"
                ],
            ],
        ];
    }

    /**
     * Create logger using given handlers.
     *
     * @param string $name
     * @param string $level
     * @param array $handlers (default: [])
     * @param array $processors (default: [])
     */
    public function __construct(string $name, string $level, array $handlers = [], array $processors = [])
    {
        parent::__construct($name, $handlers, $processors);
        $extra_args = compact('name', 'level');
        foreach (static::config('builtin_processors', false, []) as $processor) {
            if (is_callable($processor)) {
                $this->pushProcessor($processor);
                continue;
            }
            if (is_string($processor)) {
                $this->pushProcessor(static::processor($processor, $extra_args));
                continue;
            }
            $this->pushProcessor(Reflector::instantiate($processor));
        }
    }

    /**
     * Get the default setting processor of given class.
     *
     * @param string $class
     * @param array $args (default: [])
     * @return ProcessorInterface
     */
    public static function processor(string $class, array $args = []) : ProcessorInterface
    {
        $defaults = static::config("processors.{$class}", false);
        if ($defaults === null) {
            return Reflector::create($class, $args);
        }
        $rc          = new \ReflectionClass($class);
        $constractor = $rc->getConstructor();
        return Reflector::create($class, Reflector::mergeArgs($constractor ? $constractor->getParameters() : [], $defaults, $args));
    }

    /**
     * Get the default setting formatter of given class.
     *
     * @param string $class
     * @param array $args (default: [])
     * @return FormatterInterface
     */
    public static function formatter(string $class, array $args = []) : FormatterInterface
    {
        $defaults = static::config("formatters.{$class}", false);
        if ($defaults === null) {
            return Reflector::create($class, $args);
        }
        $rc          = new \ReflectionClass($class);
        $constractor = $rc->getConstructor();
        return Reflector::create($class, Reflector::mergeArgs($constractor ? $constractor->getParameters() : [], $defaults, $args));
    }

    /**
     * {@inheritDoc}
     *
     * Override for use Rebet DateTime class to 'datetime' attribute creation.
     */
    public function addRecord($level, $message, array $context = [])
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }

        $levelName = static::getLevelName($level);

        // check if any handler will handle this message so we can return early and save cycles
        $handlerKey = null;
        reset($this->handlers);
        while ($handler = current($this->handlers)) {
            if ($handler->isHandling(['level' => $level])) {
                $handlerKey = key($this->handlers);
                break;
            }

            next($this->handlers);
        }

        if (null === $handlerKey) {
            return false;
        }

        $record = [
            'message'    => $message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => $levelName,
            'channel'    => $this->name,
            'datetime'   => DateTime::now(static::$timezone)->toNativeDateTime(), // Use Rebet DateTime class for create datetime.
            'extra'      => [],
        ];

        try {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }

            while ($handler = current($this->handlers)) {
                if (true === $handler->handle($record)) {
                    break;
                }

                next($this->handlers);
            }
        } catch (\Exception $e) {
            $this->handleException($e, $record);
        }

        return true;
    }
}
