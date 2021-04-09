<?php
namespace Rebet\Log\Driver\Monolog;

use Monolog\Logger as MonologLogger;
use Monolog\Processor\ProcessIdProcessor;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\DateTime\DateTimeZone;
use Rebet\Tools\Reflection\Reflector;
use Throwable;

/**
 * Monolog Driver Class
 *
 * This class will set builtin processors defined in configure.
 * In liblary default defined bultin processors below,
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
            'processors' => [
                ProcessIdProcessor::class,
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
     * @param string|\DateTimeZone|null $timezone (default: null for use Datetime.default_timezone configure)
     */
    public function __construct(string $name, string $level, array $handlers = [], array $processors = [], $timezone = null)
    {
        parent::__construct($name, $handlers, $processors, new DateTimeZone($timezone ?? DateTime::config('default_timezone')));
        $extra_args = compact('name', 'level');
        foreach (static::config('processors', false, []) as $processor) {
            if (is_callable($processor)) {
                $this->pushProcessor($processor);
                continue;
            }
            $this->pushProcessor(Reflector::instantiate($processor));
        }
    }

    /**
     * {@inheritDoc}
     *
     * Override for use Rebet DateTime class to 'datetime' attribute creation.
     */
    public function addRecord(int $level, string $message, array $context = []) : bool
    {
        // check if any handler will handle this message so we can return early and save cycles
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling(['level' => $level])) {
                $handlerKey = $key;
                break;
            }
        }

        if (null === $handlerKey) {
            return false;
        }

        $levelName = static::getLevelName($level);

        $record = [
            'message'    => $message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => $levelName,
            'channel'    => $this->name,
            'datetime'   => DateTime::now($this->timezone)->setDefaultFormat($this->microsecondTimestamps ? 'Y-m-d\TH:i:s.uP' : 'Y-m-d\TH:i:sP'), // Use Rebet DateTime class for create datetime.
            'extra'      => [],
        ];

        try {
            foreach ($this->processors as $processor) {
                $record = $processor($record);
            }

            // advance the array pointer to the first handler that will handle this record
            reset($this->handlers);
            while ($handlerKey !== key($this->handlers)) {
                next($this->handlers);
            }

            while ($handler = current($this->handlers)) {
                if (true === $handler->handle($record)) {
                    break;
                }

                next($this->handlers);
            }
        } catch (Throwable $e) {
            $this->handleException($e, $record);
        }

        return true;
    }
}
