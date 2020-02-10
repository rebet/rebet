<?php
namespace Rebet\Log\Driver\Monolog;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\ProcessIdProcessor;
use Rebet\Common\Reflector;
use Rebet\Config\Configurable;
use Rebet\DateTime\DateTime;

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
     */
    public function __construct(string $name, string $level, array $handlers = [], array $processors = [])
    {
        parent::__construct($name, $handlers, $processors);
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
