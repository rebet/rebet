<?php
namespace Rebet\Log\Middleware;

use Rebet\Common\System;
use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;

/**
 * 整形済みログをウェブ画面に追加表示（HTML出力）するミドルウェア
 *
 * 本ミドルウェアはレスポンスヘッダに text/html 以外の明示的な Content-Type 指定が
 * 存在しない応答に対して視覚化」されたログ情報を追加します。
 * 主にローカル環境での開発においてログ情報を画面から即座に確認できるようにすること
 * を目的としたミドルウェアとなります。
 * なお、本ミドルウェアが出力する HTML 文書は </html> 閉じタグの後ろに追記されるため、
 * 正しい DOM 構造にならないことに注意してください。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class WebDisplayMiddleware
{
    /**
     * ブラウザ画面ログ出力ストック用バッファ
     * @var string
     */
    private $buffer = "";
    
    /**
     * ログの事後処理をします。
     *
     * @param LogContext $log ログコンテキスト
     * @param Closure $next 次のミドルウェア
     * @return string|array $formatted_log 整形済みログ
     */
    public function handle(LogContext $log, \Closure $next)
    {
        $formatted_log = $next($log);
        $display_log   = is_array($formatted_log) ? print_r($formatted_log, true) : $formatted_log ;
        
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
     * ミドルウェアのシャットダウン処理を実行します。
     */
    public function shutdown() : void
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
            if ($content_type === null || $content_type  === 'text/html') {
                echo($this->buffer);
                unset($this->buffer);
            }
        }
    }
}
