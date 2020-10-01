<?php
namespace Rebet\Log\Driver\Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonologLogger;
use Rebet\Tools\Strings;
use Rebet\Tools\System;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Log\Driver\Monolog\MonologDriver;

/**
 * Simple Browser Console Handler Class
 *
 * Some fonctions implementation are borrowed from Monolog\Handler\BrowserConsoleHandler of Seldaek/monolog ver 1.24.0 with some modifications.
 *
 * @see https://github.com/Seldaek/monolog/blob/1.24.0/src/Monolog/Handler/BrowserConsoleHandler.php
 * @see https://github.com/Seldaek/monolog/blob/1.24.0/LICENSE
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SimpleBrowserConsoleHandler extends AbstractProcessingHandler
{
    /**
     * @var array of Monolog level to console method and color style map []
     */
    public const OUTPUT_STYLES = [
        MonologLogger::DEBUG     => ['log', 'color: #333333; background-color: #f9f9f9; display: block; border-left: 8px solid #333333; padding-left: 5px; padding-right: 10px;', 'color: #333333; background-color: #ffffff; display: block; border-left: 2px solid #333333; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::INFO      => ['log', 'color: #007700; background-color: #e5ffcc; display: block; border-left: 8px solid #007700; padding-left: 5px; padding-right: 10px;', 'color: #007700; background-color: #ffffff; display: block; border-left: 2px solid #007700; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::NOTICE    => ['log', 'color: #3333cc; background-color: #eeeeff; display: block; border-left: 8px solid #3333cc; padding-left: 5px; padding-right: 10px;', 'color: #3333cc; background-color: #ffffff; display: block; border-left: 2px solid #3333cc; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::WARNING   => ['log', 'color: #ff4400; background-color: #ffffee; display: block; border-left: 8px solid #ff4400; padding-left: 5px; padding-right: 10px;', 'color: #ff4400; background-color: #ffffff; display: block; border-left: 2px solid #ff4400; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::ERROR     => ['log', 'color: #cc1111; background-color: #fff3f3; display: block; border-left: 8px solid #cc1111; padding-left: 5px; padding-right: 10px;', 'color: #cc1111; background-color: #ffffff; display: block; border-left: 2px solid #cc1111; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::CRITICAL  => ['log', 'color: #cc1111; background-color: #fff3f3; display: block; border-left: 8px solid #cc1111; padding-left: 5px; padding-right: 10px;', 'color: #cc1111; background-color: #ffffff; display: block; border-left: 2px solid #cc1111; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::ALERT     => ['log', 'color: #ffffff; background-color: #cc1111; display: block; border-left: 8px solid #cc1111; padding-left: 5px; padding-right: 10px;', 'color: #cc1111; background-color: #ffffff; display: block; border-left: 2px solid #cc1111; padding-left: 5px; padding-right: 10px;'],
        MonologLogger::EMERGENCY => ['log', 'color: #ffffff; background-color: #cc1111; display: block; border-left: 8px solid #cc1111; padding-left: 5px; padding-right: 10px;', 'color: #cc1111; background-color: #ffffff; display: block; border-left: 2px solid #cc1111; padding-left: 5px; padding-right: 10px;'],
    ];

    /**
     * @var boolean
     */
    protected static $initialized = false;

    /**
     * @var array of log records
     */
    protected static $records = [];

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter() : FormatterInterface
    {
        return new TextFormatter();
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record) : void
    {
        // Accumulate records
        static::$records[] = $record;

        // Register shutdown handler if not already done
        if (!static::$initialized) {
            static::$initialized = true;
            if (PHP_SAPI !== 'cli') {
                register_shutdown_function([static::class, 'send']);
            }
        }
    }

    /**
     * Convert records to javascript console commands and send it to the browser.
     * This method is automatically called on PHP shutdown if output is HTML or Javascript.
     *
     * @return void
     */
    public static function send() : void
    {
        $format = static::getResponseFormat();
        if ($format === 'unknown') {
            return;
        }

        if (!empty(static::$records)) {
            $scripts = static::generateScript();
            echo($format === 'html' ? "<script>{$scripts}</script>" : $scripts);
            static::clear();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function close() : void
    {
        static::clear();
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        static::clear();
    }

    /**
     * Clear all logged records
     */
    public static function clear() : void
    {
        static::$records = [];
    }

    /**
     * Checks the format of the response
     *
     * If Content-Type is set to application/javascript or text/javascript -> js
     * If Content-Type is set to text/html, or is unset -> html
     * If Content-Type is anything else -> unknown
     *
     * @return string One of 'js', 'html' or 'unknown'
     */
    protected static function getResponseFormat()
    {
        // Check content type
        foreach (System::headers_list() as $header) {
            if (stripos($header, 'content-type:') === 0) {
                // This handler only works with HTML and javascript outputs
                // text/javascript is obsolete in favour of application/javascript, but still used
                if (stripos($header, 'application/javascript') !== false || stripos($header, 'text/javascript') !== false) {
                    return 'js';
                }
                if (stripos($header, 'text/html') === false) {
                    return 'unknown';
                }
                break;
            }
        }

        return 'html';
    }

    /**
     * Generate script cord for browser's javascript console.
     *
     * @return string
     */
    protected static function generateScript() : string
    {
        $script = [];
        foreach (static::$records as $record) {
            [$headline, $details]         = Strings::split($record['formatted'], "\n", 2);
            [$method, $h_style, $d_style] = static::OUTPUT_STYLES[$record['level'] ?? MonologDriver::DEBUG];

            if ($details) {
                $script[] = 'c.groupCollapsed('.static::quote("%c{$headline}").', '.static::quote($h_style).');';
                $script[] = "c.{$method}(".static::quote("%c{$details}").', '.static::quote($d_style).');';
                $script[] = 'c.groupEnd();';
            } else {
                $script[] = "c.{$method}(".static::quote("%c{$headline}").', '.static::quote($h_style).');';
            }
        }

        return "(function (c) {if (c && c.groupCollapsed) {\n" . implode("\n", $script) . "\n}})(console);";
    }

    /**
     * Return quoted string
     *
     * @param string $arg
     * @return string
     */
    protected static function quote(string $arg) : string
    {
        return '"' . addcslashes($arg, "\"\n\\") . '"';
    }
}
