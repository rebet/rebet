<?php
namespace Rebet\Config;

use Rebet\Common\TransparentlyDotAccessible;

/**
 * コンフィグ遅延評価 クラス
 * 
 * コンフィグ設定に置いて、遅延評価を行いたい場合に利用します。
 * ※本オブジェクトは Config::promise() ファサードを利用して構築できます。
 * 
 * @see Rebet\Config\Config::promise()
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ConfigPromise implements TransparentlyDotAccessible {
    
    /**
     * 遅延評価式
     * @var callable
     */
    private $promise = null;

    /**
     * 最初の遅延評価で値を確定するか否か
     * @var bool
     */
    private $only_once = true;

    /**
     * 遅延実行で確定した値
     * ※ only_once が true の場合に利用
     * @var mixed
     */
    private $evaluated_value = null;

    /**
     * 既に評価済みか否か
     * ※ only_once が true の場合に利用
     * @var mixed
     */
    private $is_evaluated = false;

    /**
     * コンフィグ遅延評価クラスを構築します。
     * 
     * @param callable $promise 遅延評価式
     * @param bool $only_once 最初の遅延評価で値を確定するか否か（デフォルト：true）
     */
    public function __construct(callable $promise, bool $only_once = true) {
        $this->promise   = $promise;
        $this->only_once = $only_once;
    }

    /**
     * 遅延評価結果を取得します。
     */
    public function get() {
        if(!$this->only_once) { return ($this->promise)(); }
        if($this->is_evaluated) { return $this->evaluated_value; }
        $this->evaluated_value = ($this->promise)();
        $this->is_evaluated    = true;
        return $this->evaluated_value;
    }
}