<?php
declare(strict_types=1);

namespace Rebet\Log\Driver\Monolog;

use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\ProcessIdProcessor;
use Rebet\Log\Driver\NameableDriver;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\DateTime\DateTimeZone;
use Rebet\Tools\Reflection\Reflector;

/**
 * Monolog Driver Class
 *
 * This class will set builtin processors defined in configure.
 * In liblary default defined bultin processors below,
 *   - ProcessIdProcessor
 *
 * Usage: (Parameter of Constractor)
 *   'driver'     [*] MonologDriver::class,
 *   'handlers'   [ ] HandlerInterface[] for optional stack of handlers, the first one in the array is called first, etc. (default: [])
 *   'processors' [ ] array of callable processors. (default: [])
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MonologDriver extends MonologLogger implements NameableDriver // @phpstan-ignore class.extendsFinalByPhpDoc
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
     * @param array<int, \Monolog\Handler\HandlerInterface> $handlers (default: [])
     * @param array<int, callable> $processors (default: [])
     * @param string|\DateTimeZone|null $timezone (default: null for use Datetime.default_timezone configure)
     */
    public function __construct(array $handlers = [], array $processors = [], $timezone = null)
    {
        // The default logger name is 'rebet', but usually overridden the channel name by Log
        parent::__construct('rebet', $handlers, $processors, new DateTimeZone($timezone ?? DateTime::config('default_timezone')));
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
     */
    public function setName(string $name) : self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Override for use Rebet DateTime class (which is testable via DateTime::setTestNow())
     * as the source of the record 'datetime' attribute creation.
     */
    public function addRecord(int|Level $level, string $message, array $context = [], JsonSerializableDateTimeImmutable|null $datetime = null) : bool
    {
        if ($datetime === null) {
            $datetime = new JsonSerializableDateTimeImmutable($this->microsecondTimestamps, $this->timezone);
            $datetime = $datetime->modify(DateTime::now($this->timezone)->format('Y-m-d H:i:s.u'));
        }
        return parent::addRecord($level, $message, $context, $datetime);
    }
}
