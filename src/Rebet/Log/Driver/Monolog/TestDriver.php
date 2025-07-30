<?php
namespace Rebet\Log\Driver\Monolog;

use Monolog\Handler\TestHandler;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;

/**
 * Test Driver Class
 *
 * This class based on Monolog\Handler\TestHandler.
 *
 * Usage: (Parameter of Constractor)
 *     'driver'          [*] StderrDriver::class,
 *     'level'           [*] string of LogLevel::*,
 *     'format'          [ ] string of format template (default: null for use TextFormat class config)
 *     'stringifiers'    [ ] placeholder stringify setting of format template (default: [] for use TextFormat class config)
 *     'bubble'          [ ] boolean of bubble (default: true)
 *
 * TestHandler delegate methods.
 * ========================================================
 * @method array getRecords()
 * @method void clear()
 * @method bool hasRecords($level)
 * @method bool hasRecord($record, $level)
 * @method bool hasRecordThatContains($message, $level)
 * @method bool hasRecordThatMatches($regex, $level)
 * @method bool hasRecordThatPasses($predicate, $level)
 *
 * @method bool hasEmergency($record)
 * @method bool hasAlert($record)
 * @method bool hasCritical($record)
 * @method bool hasError($record)
 * @method bool hasWarning($record)
 * @method bool hasNotice($record)
 * @method bool hasInfo($record)
 * @method bool hasDebug($record)
 *
 * @method bool hasEmergencyRecords()
 * @method bool hasAlertRecords()
 * @method bool hasCriticalRecords()
 * @method bool hasErrorRecords()
 * @method bool hasWarningRecords()
 * @method bool hasNoticeRecords()
 * @method bool hasInfoRecords()
 * @method bool hasDebugRecords()
 *
 * @method bool hasEmergencyThatContains($message)
 * @method bool hasAlertThatContains($message)
 * @method bool hasCriticalThatContains($message)
 * @method bool hasErrorThatContains($message)
 * @method bool hasWarningThatContains($message)
 * @method bool hasNoticeThatContains($message)
 * @method bool hasInfoThatContains($message)
 * @method bool hasDebugThatContains($message)
 *
 * @method bool hasEmergencyThatMatches($message)
 * @method bool hasAlertThatMatches($message)
 * @method bool hasCriticalThatMatches($message)
 * @method bool hasErrorThatMatches($message)
 * @method bool hasWarningThatMatches($message)
 * @method bool hasNoticeThatMatches($message)
 * @method bool hasInfoThatMatches($message)
 * @method bool hasDebugThatMatches($message)
 *
 * @method bool hasEmergencyThatPasses($message)
 * @method bool hasAlertThatPasses($message)
 * @method bool hasCriticalThatPasses($message)
 * @method bool hasErrorThatPasses($message)
 * @method bool hasWarningThatPasses($message)
 * @method bool hasNoticeThatPasses($message)
 * @method bool hasInfoThatPasses($message)
 * @method bool hasDebugThatPasses($message)
 * ========================================================
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TestDriver extends MonologDriver
{
    /**
     * @var TestHandler
     */
    protected $handler;

    /**
     * Create logging driver for test.
     *
     * @param string $level
     * @param string|null $format (default: null)
     * @param array $stringifiers (default: [])
     * @param boolean $bubble (default: true)
     */
    public function __construct(string $level, string $format = null, array $stringifiers = [], bool $bubble = true)
    {
        $this->handler = new TestHandler($level, $bubble);
        $this->handler->setFormatter(new TextFormatter($format, $stringifiers));
        parent::__construct([$this->handler]);
    }

    /**
     * Delegate methods to Monolog\Handler\TestHandler instance.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->handler->$method(...$args);
    }

    /**
     * Get log records as formatted string.
     *
     * @return string
     */
    public function formatted() : string
    {
        $log = "";
        foreach ($this->handler->getRecords() as $record) {
            $log .= $record['formatted'] ?? '' ;
        }
        return $log;
    }
}
