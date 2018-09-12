<?php
namespace Rebet\Log\Plugin;

use Rebet\Common\System;
use Rebet\DateTime\DateTime;
use Rebet\Log\LogLevel;
use Rebet\Log\Handler\LogHandler;

/**
 * 整形済みログをウェブ画面にHTML出力するログプラグイン
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class WebDisplayPlugin implements LogPlugin {
    /**
     * ブラウザ画面ログ出力ストック用バッファ
     * @var string
     */
    private $buffer = "";
    
    /**
     * ログプラグインを作成します。
     * @return static
     */
    public static function create() : LogPlugin {
        return new static();
    }

    /**
     * ログの事前処理します。
     * 
     * @param LogHandler $handler ログハンドラ
     * @param DateTime $now 現在時刻
     * @param LogLevel $level ログレベル
     * @param array $extra エキストラ情報
     * @return void
     */
    public function prehook(LogHandler $handler, DateTime $now, LogLevel $level, array &$extra) : void {
        // Do nothing.
    }

    /**
     * ログの事後処理をします。
     * 
     * @param LogHandler $handler ログハンドラ
     * @param DateTime $now 現在時刻
     * @param LogLevel $level ログレベル
     * @param string|array $formatted_log 整形済みログ
     */
    public function posthook(LogHandler $handler, DateTime $now, LogLevel $level, $formatted_log) : void {
        if(is_array($formatted_log)) {
            $formatted_log = print_r($formatted_log, true);
        }
        
        switch ($level) {
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
        
        $mark         = substr_count($formatted_log,"\n") > 1 ? "☰" : "　" ;
        $message      = preg_replace('/\n/s', '<br />', str_replace(' ', '&nbsp;', htmlspecialchars($formatted_log)));
        $this->buffer = <<<EOS
<div style="box-sizing: border-box; height:20px; overflow-y:hidden; cursor:pointer; margin:5px; padding:4px 10px 4px 26px; border-left:8px solid {$fc}; color:{$fc}; background-color:{$bc};display: block;font-size:12px; line-height: 1.2em; word-break : break-all;font-family: Consolas, 'Courier New', Courier, Monaco, monospace;text-indent:-19px;text-align: left;"
    ondblclick="javascript: this.style.height=='20px' ? this.style.height='auto' : this.style.height='20px'">
{$mark} {$message}
</div>
EOS;
    }

    /**
     * プラグインのシャットダウン処理を実行します。
     * 本メソッドは shotdown_handler でコールされます。
     */
    public function shutdown() : void {
        if(!empty($this->buffer)) {
            foreach (System::headers_list() as $header) {
                if(preg_match('/content-type: text\/html/', strtolower($header))) {
                    echo $this->buffer;
                    return;
                }
            } 
        }
    }
}
