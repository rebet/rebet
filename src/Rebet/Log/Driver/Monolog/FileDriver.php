<?php
namespace Rebet\Log\Driver\Monolog;

use Monolog\Handler\RotatingFileHandler;
use Rebet\Common\Arrays;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Log\Driver\Monolog\Handler\SimpleBrowserConsoleHandler;

/**
 * File Driver Class
 *
 * This class based on Monolog\Handler\RotatingFileHandler.
 *
 * Usage: (Parameter of Constractor)
 *     'driver'               [*] FileDriver::class,
 *     'name'                 [*] string of name (usualy same as channel name),
 *     'level'                [*] string of LogLevel::*,
 *     'filename'             [*] string of file path,
 *     'filename_format'      [ ] string of filename_format that contains {filename} and {date} placeholder (default: '{filename}-{date}'),
 *     'filename_date_format' [ ] string of filename {date} placeholder format (default: 'Y-m-d'),
 *     'max_files'            [ ] int of max files count (default: 0)
 *     'file_permission'      [ ] int of log file permission (default: 0644)
 *     'use_locking'          [ ] bool of file locking (default: false)
 *     'with_browser_console' [ ] bool of display log with browser console (default: false)
 *     'format'               [ ] string of format template (default: null for use MonologDriver class config)
 *     'datetime_format'      [ ] string of datetime format (default: null for use MonologDriver class config)
 *     'bubble'               [ ] boolean of bubble (default: true)
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FileDriver extends MonologDriver
{
    /**
     * Create File ouput logging driver.
     *
     * @param string $name
     * @param string $level
     * @param string $filename
     * @param string $filename_format (default: '{filename}-{date}')
     * @param string $filename_date_format (default: 'Y-m-d')
     * @param int $max_files (default: 0)
     * @param int $file_permission (default: 0644)
     * @param bool $use_locking (default: false)
     * @param bool $with_browser_console (default: false)
     * @param string|null $format (default: null)
     * @param string|null $datetime_format (default: null)
     * @param boolean $bubble (default: true)
     */
    public function __construct(
        string $name,
        string $level,
        string $filename,
        string $filename_format      = '{filename}-{date}',
        string $filename_date_format = 'Y-m-d',
        int $max_files               = 0,
        int $file_permission         = 0644,
        bool $use_locking            = false,
        bool $with_browser_console   = false,
        ?string $format              = null,
        ?string $datetime_format     = null,
        bool $bubble                 = true
    ) {
        $rfh = new RotatingFileHandler($filename, $max_files, $level, $bubble, $file_permission, $use_locking);
        $rfh->setFilenameFormat($filename_format, $filename_date_format);
        $rfh->setFormatter(static::formatter(TextFormatter::class, Arrays::compact(compact('format', 'datetime_format'))));
        $handlers = [$rfh];

        if ($with_browser_console) {
            $sbch = new SimpleBrowserConsoleHandler($level, $bubble);
            $sbch->setFormatter(static::formatter(TextFormatter::class, Arrays::compact(compact('format', 'datetime_format'))));
            $handlers[] = $sbch;
        }

        parent::__construct($name, $level, $handlers);
    }
}
