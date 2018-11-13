<?php
namespace Rebet\Log\Middleware;

use Rebet\Common\System;
use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

/**
 * Web Display Middleware Class
 *
 * This middleware to add formatted logs to the web screen (HTML output).
 *
 * This middleware adds log information visualized to the response
 * whose response header does not have an explicit Content-Type designation other than text/html.
 * It is middleware aiming at making it possible to immediately confirm the log information
 * from the screen in development mainly in the local environment.
 *
 * Please be aware that the HTML document output by this middleware will be appended to the back of the </ html> close tag,
 * so it will not be in the correct DOM structure.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class WebDisplay
{
    /**
     * Browser screen log output buffer for stock
     *
     * @var string
     */
    private $buffer = "";
    
    /**
     * Handle log context.
     *
     * @param LogContext $log
     * @param Closure $next
     * @return string|array|null $formatted_log
     */
    public function handle(LogContext $log, \Closure $next)
    {
        $formatted_log = $next($log);
        if ($formatted_log === null) {
            return null;
        }

        $display_log = is_array($formatted_log) ? print_r($formatted_log, true) : $formatted_log ;
        switch ($log->level) {
            case LogLevel::TRACE():
                $fc = '#666666'; $bc = '#f9f9f9';
                break;
            case LogLevel::DEBUG():
                $fc = '#3333cc'; $bc = '#eeeeff';
                break;
            case LogLevel::INFO():
                $fc = '#229922'; $bc = '#eeffee';
                break;
            case LogLevel::WARN():
                $fc = '#ff6e00'; $bc = '#ffffee';
                break;
            case LogLevel::ERROR():
            case LogLevel::FATAL():
                $fc = '#ee3333'; $bc = '#ffeeee';
                break;
        }
        
        $mark          = substr_count($display_log, "\n") > 1 ? "☰" : "　" ;
        $message       = preg_replace('/\n/s', '<br />', str_replace(' ', '&nbsp;', htmlspecialchars($display_log)));
        $this->buffer .= <<<EOS
<div style="box-sizing: border-box; height:20px; overflow-y:hidden; cursor:pointer; margin:5px; padding:4px 10px 4px 26px; border-left:8px solid {$fc}; color:{$fc}; background-color:{$bc};display: block;font-size:12px; line-height: 1.2em; word-break : break-all;font-family: Consolas, 'Courier New', Courier, Monaco, monospace;text-indent:-19px;text-align: left;"
    ondblclick="javascript: this.style.height=='20px' ? this.style.height='auto' : this.style.height='20px'">
{$mark} {$message}
</div>
EOS;

        return $formatted_log;
    }

    /**
     * Terminate the middleware
     */
    public function terminate() : void
    {
        if (!empty($this->buffer)) {
            $matches      = [];
            $content_type = null;
            foreach (System::headers_list() as $header) {
                if (preg_match('/content-type *: *(.*?);/', strtolower($header), $matches)) {
                    $content_type = $matches[1];
                    break;
                }
            }
            if ($content_type === null || $content_type === 'text/html') {
                echo($this->buffer);
                $this->buffer = null;
            }
        }
    }
}
