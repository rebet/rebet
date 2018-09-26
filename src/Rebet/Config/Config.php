<?php
namespace Rebet\Config;

use Rebet\Common\Util;
use Rebet\Common\ArrayUtil;
use Rebet\Common\OverrideOption;
use Rebet\Common\StringUtil;

/**
 * コンフィグ クラス
 *
 * 各種設定を統一的に扱うクラス。
 * 本クラスのコンフィグには以下の4段階の設定があり、
 *
 * 　1. ライブラリコンフィグ
 * 　2. フレームワークコンフィグ
 * 　3. アプリケーションコンフィグ
 * 　4. ランタイムコンフィグ
 *
 * 【優先度高 4 > 3 > 2 > 1 優先度低】 の優先度に従って設定を上書きすることができます。
 * また、各設定は以下のように定義／動作します。
 *
 * 　1. ライブラリコンフィグ
 * 　　　⇒ 各クラス定義にて Configurable trait の実装
 * 　2. フレームワークコンフィグ
 * 　　　⇒ フレームワーク初期化処理にて Config::framework() で設定／上書き
 * 　3. アプリケーションコンフィグ
 * 　　　⇒ アプリケーション初期化処理にて Config::application() で設定／上書き
 * 　4. ランタイムコンフィグ
 * 　　　⇒ アプリケーション実行中に Config::runtime() で設定／上書き
 * 　　　⇒ Configurable 実装クラスにて protected setConfig() を利用した個別実装のコンフィグ設定メソッドで設定／上書き
 *
 * なお、上記レイヤー別の上書きは挙動は Rebet\Common\ArrayUtil::override() と同様の動作をします。
 *
 * @todo frameworkレイヤーは不要では？ 要件等
 * @todo i18n 関連の考察
 * @todo 現在の最終設定（全て）を一覧するメソッドなどの実装
 * @todo 現在の設定をレイヤー別に参照するメソッドなどの実装
 *
 * @see Rebet\Config\Configurable
 * @see Rebet\Common\ArrayUtil
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
final class Config
{
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * コンフィグ設定
     * 構造は下記の通り
     *
     * config = [
     *    'Layer' => [
     *       'Section' => [
     *           'key' => value,
     *       ],
     *    ],
     * ]
     *
     * @var array
     */
    private static $config = [
        Layer::LIBRARY     => [],
        Layer::FRAMEWORK   => [],
        Layer::APPLICATION => [],
        Layer::RUNTIME     => [],
    ];

    /**
     * コンフィグのオプション設定
     *
     * option = [
     *    'Layer' => [
     *       'Section' => [
     *           'key' => OverrideOption,
     *       ],
     *    ],
     * ]
     *
     * @var array
     */
    private static $option = [
        Layer::LIBRARY     => [],
        Layer::FRAMEWORK   => [],
        Layer::APPLICATION => [],
        Layer::RUNTIME     => [],
    ];

    /**
     * コンフィグデータを全てクリアします
     */
    public static function clear() : void
    {
        static::$config = [
            Layer::LIBRARY     => [],
            Layer::FRAMEWORK   => [],
            Layer::APPLICATION => [],
            Layer::RUNTIME     => [],
        ];

        static::$option = [
            Layer::LIBRARY     => [],
            Layer::FRAMEWORK   => [],
            Layer::APPLICATION => [],
            Layer::RUNTIME     => [],
        ];
    }

    /**
     * 対象レイヤーのコンフィグを設定／上書きします。
     * 本設定は ArrayUtil::override() による上書き設定となります。
     */
    private static function put(string $layer, array $config) : void
    {
        $config = self::analyzeOption($config, static::$option[$layer]);
        static::$config[$layer] = ArrayUtil::override(static::$config[$layer], $config, static::$option[$layer]);
    }
    
    /**
     * コンフィグ設定
     *
     * @param array $config
     * @param array $option
     * @return void
     */
    private static function analyzeOption(array $config, array &$option)
    {
        if (!\is_array($config) || ArrayUtil::isSequential($config)) {
            return $config;
        }

        $analyzed = [];
        foreach ($config as $key => $value) {
            [$key, $option] = OverrideOption::split($key);
            if ($option !== null) {
                $option[$key] = $option;
            }
            
            if (\is_array($value) && !ArrayUtil::isSequential($value)) {
                $option[$key] = [];
                $value = static::analyzeOption($value, $option[$key]);
            }

            if ($option === null && isset($option[$key]) && (!\is_array($option[$key]) || empty($option[$key]))) {
                unset($option[$key]);
            }

            $analyzed[$key] = $value;
        }
        return $analyzed;
    }

    /**
     * フレームワークコンフィグを設定します。
     * 本設定は array_merge による上書き設定となります。
     *
     * ex)
     * Config::framework([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
     *     DateTime::class => [
     *         'default_format' => 'Y/m/d H:i:s',
     *     ],
     *     'SectionName' => [
     *          'key' => 'value',
     *     ],
     * ]);
     */
    public static function framework(array $config) : void
    {
        self::put(Layer::FRAMEWORK, $config);
    }

    /**
     * アプリケーションコンフィグを設定します。
     * 本設定は array_merge による上書き設定となります。
     *
     * ex)
     * Config::application([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
     *     DateTime::class => [
     *         'default_format' => 'Y/m/d H:i:s',
     *     ],
     *     'SectionName' => [
     *          'key' => 'value',
     *     ],
     * ]);
     */
    public static function application(array $config) : void
    {
        self::put(Layer::APPLICATION, $config);
    }

    /**
     * ランタイムコンフィグを設定します。
     * 本設定は array_merge による上書き設定となります。
     *
     * ex)
     * Config::runtime([
     *     Dao::class => [
     *         'database' => 'rebet',
     *         'user' => 'rebet',
     *         'password' => 'password',
     *     ],
     *     DateTime::class => [
     *         'default_format' => 'Y/m/d H:i:s',
     *     ],
     *     'SectionName' => [
     *          'key' => 'value',
     *     ],
     * ]);
     */
    public static function runtime(array $config) : void
    {
        self::put(Layer::RUNTIME, $config);
    }
    
    /**
     * 対象のコンフィグに指定の設定が定義されているかチェックします。
     * なお、キーセレクタの要素に数値のみのインデックスアクセスが含まれる場合、本メソッドは例外を throw します。
     *
     * @param array $config チェック対象のコンフィグ
     * @param string $section チェック対象のセクション
     * @param string $key|null チェック対象のキー（.区切りで階層指定可）
     * @return int true: 定義あり, false: 定義なし
     * @throws LogicException
     */
    private static function isDefine(array $config, string $section, ?string $key) : bool
    {
        if (Util::isBlank($key)) {
            return isset($config[$section]);
        }
        foreach (\explode('.', $key) as $value) {
            if (\ctype_digit($value)) {
                throw new \LogicException("Invalid config key access, the key '{$key}' contains digit only part.");
            }
        }
        return isset($config[$section]) && Util::has($config[$section], $key) ;
    }

    /**
     * コンフィグの設定値を取得します。
     * ※キー名に blank を指定するとすべてのコンフィグ設定を取得します
     *
     * なお、キーセレクタの要素に数値のみのインデックスアクセスが含まれる場合、本メソッドは例外を throw します。
     * インデックス指定でのアクセスが必要な場合は対象の配列をデータを取得してから個別にアクセスして下さい。
     *
     * @param string $section セクション
     * @param string|null $key 設定キー名[.区切りで階層指定可]（デフォルト：null）
     * @param bool $required 必須項目指定（デフォルト：true） … true指定時、設定値が blank だと例外を throw します
     * @param ?mixed $default 必須項目指定が false で、値が未設定の場合にこの値が返ります。
     * @return ?mixed 設定値
     * @throws ConfigNotDefineException
     * @throws LogicException
     */
    public static function get(string $section, ?string $key = null, bool $required = true, $default = null)
    {
        $diffs = [];
        
        foreach ([
            Layer::RUNTIME     => 'Overwritten with blank at runtime layer.',
            Layer::APPLICATION => 'Overwritten with blank at application layer.',
            Layer::FRAMEWORK   => 'Please define at application layer.',
        ] as $layer => $extra_message) {
            if (self::isDefine(static::$config[$layer], $section, $key)) {
                $value = Util::isBlank($key) ? static::$config[$layer][$section] : Util::get(static::$config[$layer][$section], $key);
                $value = Util::bvl($value, $default);
                if ($required && Util::isBlank($value) && empty($diffs)) {
                    throw new ConfigNotDefineException("Required config {$section}".($key ? "#{$key}" : "")." is blank. {$extra_message}");
                }
                if (empty($diffs) && (!\is_array($value) || ArrayUtil::isSequential($value))) {
                    return $value;
                }
                $diffs[$layer] = $value;
            }
        }

        // ライブラリコンフィグ遅延ロード
        static::loadLibraryConfig($section);
        
        // ライブラリコンフィグ
        $value = Util::isBlank($key) ? static::$config[Layer::LIBRARY][$section] : Util::get(static::$config[Layer::LIBRARY][$section], $key);
        $value = Util::bvl($value, $default);
        if ($required && Util::isBlank($value) && empty($diffs)) {
            if (self::isDefine(static::$config[Layer::LIBRARY], $section, $key)) {
                throw new ConfigNotDefineException("Required config {$section}".($key ? "#{$key}" : "")." is blank. Please define at application or framework layer.");
            }
            throw new ConfigNotDefineException("Required config {$section}".($key ? "#{$key}" : "")." is not define. Please check config key name.");
        }
        return static::override($section, $key, $value, $diffs);
    }

    /**
     * 差分スタックと値をマージして結果を返します。
     *
     * @param string $section セクション
     * @param string|null $key 設定キー名[.区切りで階層指定可]（デフォルト：null）
     * @param mixed $value
     * @param array $diffs
     * @return mixed
     */
    protected static function override(string $section, ?string $key = null, $value, array $diffs)
    {
        if (empty($diffs)) {
            return $value;
        }
        foreach (\array_reverse($diffs) as $layer => $diff) {
            $value = ArrayUtil::override($value, $diff, Util::get(static::$option[$layer], $key === null ? $section : "{$section}.{$key}", []));
        }
        return $value;
    }

    /**
     * ライブラリコンフィグをロードします。
     *
     * @param string $section
     * @return void
     */
    protected static function loadLibraryConfig(string $section) : void
    {
        if (isset(static::$config[Layer::LIBRARY][$section])) {
            return;
        }
        static::$option[Layer::LIBRARY][$section] = [];
        static::$config[Layer::LIBRARY][$section] = method_exists($section, 'defaultConfig') ? static::analyzeOption($section::defaultConfig(), static::$option[Layer::LIBRARY][$section]) : [] ;
    }

    /**
     * コンフィグの設定値からインスタンスを生成します。
     *
     * @see Rebet\Common\Util::instantiate()
     *
     * @param string $section セクション
     * @param string $key 設定キー名（.区切りで階層指定可）
     * @param bool $required 必須項目指定（デフォルト：true） … true指定時、設定値が blank だと例外を throw します
     * @param ?mixed $default 必須項目指定が false で、値が未設定の場合にこの値が返ります。
     * @return ?mixed インスタンス
     * @throws ConfigNotDefineException
     * @throws LogicException
     */
    public static function instantiate(string $section, string $key, bool $required = true, $default = null)
    {
        return Util::instantiate(self::get($section, $key, $required, $default));
    }

    /**
     * コンフィグの設定が定義されているかチェックします。
     *
     * なお、キーセレクタの要素に数値のみのインデックスアクセスが含まれる場合、本メソッドは例外を throw します。
     * インデックス指定でのアクセスが必要な場合は対象の配列をデータを取得してから個別にアクセスして下さい。
     *
     * @param string $section セクション
     * @param string $key 設定キー名（.区切りで階層指定）
     * @return bool true: 定義済み, false: 未定義
     * @throws LogicException
     */
    public static function has(string $section, string $key) : bool
    {
        foreach ([Layer::RUNTIME, Layer::APPLICATION, Layer::FRAMEWORK] as $layer) {
            if (self::isDefine(static::$config[$layer], $section, $key)) {
                return true;
            }
        }

        // ライブラリコンフィグ遅延ロード
        static::loadLibraryConfig($section);

        // ライブラリコンフィグ
        return self::isDefine(static::$config[Layer::LIBRARY], $section, $key);
    }

    /**
     * 他のセクションのコンフィグ設定を共有するリファラを返します。
     *
     * ex)
     * // 日時に関連したクラスでの定義例
     * public static function defaultConfig() {
     *     return [
     *         'default_format'   => 'Y-m-d H:i:s',
     *         'default_timezone' => Config::refer(App::class, 'timezone', date_default_timezone_get() ?: 'UTC'),
     *     ];
     * }
     *
     * @param string $section 参照先セクション
     * @param string $key 参照先キー名（.区切りで階層指定）
     * @param mixed $default 参照先がブランクの場合のデフォルト値（デフォルト：null）
     * @return ConfigReferrer
     */
    public static function refer(string $section, string $key, $default = null) : ConfigReferrer
    {
        return new ConfigReferrer($section, $key, $default);
    }

    /**
     * 設定値の確定をその設定が参照されるまで遅延する遅延評価式を返します。
     *
     * ex)
     * // getenv を利用しているコンフィグが DotEnv::load() より前にロードされる場合
     * public static function defaultConfig() {
     *     return [
     *         'env' => Config::promise(function(){ return getenv('APP_ENV') ?: 'development' ; }),
     *     ];
     * }
     *
     * @param \Closure $promise 遅延評価式
     * @param bool $only_once 最初の遅延評価で値を確定するか否か（デフォルト：true）
     * @return ConfigPromise
     */
    public static function promise(\Closure $promise, bool $only_once = true) : ConfigPromise
    {
        return new ConfigPromise($promise, $only_once);
    }
}
