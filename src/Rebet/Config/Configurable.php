<?php
namespace Rebet\Config;

use Rebet\Common\ArrayUtil;

/**
 * コンフィグ設定を利用する トレイト
 *
 * 本トレイトを実装することで、対象クラス内にて以下の形でコンフィグを利用できます。
 *
 *   self::config('key');
 *   //or static::config('key');
 *
 * また、外部からは以下のようにコンフィグ設定にアクセスできます。
 *
 *   ConfigurableImplement::config('key');
 *
 * なお、上記のアクセスは下記コードと同義です。
 *
 *   Config::get(ConfigurableImplement::class, 'key');
 *
 * そのため、本トレイトにて実装されたデフォルトコンフィグ設定は以下のように上書可能となります。
 *
 *   Config::application([
 *       ConfigurableImplement::class => [
 *           'key' => 'new value'
 *       ]
 *   ]);
 *
 * @see Rebet\Config\Config
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Configurable
{
    
    /**
     * デフォルトコンフィグ設定。
     * 各トレイト導入クラスにてライブラリのデフォルト設定を定義して下さい。
     * ここで返される設定は自動的にトレイト実装クラス名のセクションに分類されるため、
     * セクションの指定は不要です。
     *
     * ex)
     * // データベースに関連したクラスでの定義例
     * public static function defaultConfig() {
     *     return [
     *         'driver' => 'mysql',
     *         'host' => 'localhost',
     *         'port' => 3306,
     *         'database' => null,
     *         'user' => null,
     *     ];
     * }
     *
     * // 日時に関連したクラスでの定義例
     * public static function defaultConfig() {
     *     return [
     *         'default_format'   => 'Y-m-d H:i:s',
     *         'default_timezone' => Config::refer(App::class, 'timezone', date_default_timezone_get() ?: 'UTC'),
     *     ];
     * }
     *
     * // Configurable を実装したクラスを継承し、サブクラスで新しい設定を導入/上書する定義例
     * public static function defaultConfig() {
     *     return self::overrideConfig([
     *         'default_format' => 'M d, Y g:i A',
     *         'new_key' => 'new_value',
     *     ];
     * }
     *
     */
    abstract public static function defaultConfig() : array ;

    /**
     * 親クラスのデフォルトコンフィグ設定を差分オーバーライドします。
     *
     * @param array $config 差分コンフィグ設定
     * @return array オーバーライド後の設定内容
     */
    protected static function overrideConfig(array $diff) : array
    {
        $rc   = new \ReflectionClass(static::class);
        $base = $rc->getParentClass()->getMethod('defaultConfig')->invoke(null);
        return ArrayUtil::override($base, $diff);
    }

    /**
     * 自身のコンフィグ設定を取得します。
     *
     * @param string $key 設定キー名（.区切りで階層指定）
     * @param bool $required 必須項目指定（デフォルト：true） … true指定時、設定値が blank だと例外を throw します
     * @param ?mixed $default 必須項目指定が false で、値が未設定の場合にこの値が返ります。
     * @return ?mixed 設定値
     * @throws ConfigNotDefineException
     */
    public static function config(string $key, bool $required = true, $default = null)
    {
        return Config::get(static::class, $key, $required, $default);
    }

    /**
     * 自身のコンフィグ設定を元にインスタンス生成した値を取得します。
     *
     * @see Rebet\Config\Config::instantiate()
     * @see Rebet\Common\Util::instantiate()
     *
     * @param string $key 設定キー名（.区切りで階層指定）
     * @param bool $required 必須項目指定（デフォルト：true） … true指定時、設定値が blank だと例外を throw します
     * @param ?mixed $default 必須項目指定が false で、値が未設定の場合にこの値が返ります。
     * @return ?mixed 設定値
     * @throws ConfigNotDefineException
     */
    protected static function configInstantiate(string $key, bool $required = true, $default = null)
    {
        return Config::instantiate(static::class, $key, $required, $default);
    }

    /**
     * 自身のコンフィグ設定を更新します。
     * 本メソッドは ランタイムレイヤー のコンフィグ設定を追加します。
     *
     * @param array $config コンフィグ設定
     */
    protected static function setConfig(array $config) : void
    {
        Config::runtime([static::class => $config]);
    }
}
