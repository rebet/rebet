<?php
namespace Rebet\Config;

use Rebet\Common\Arrays;
use Rebet\Common\OverrideOption;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;

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
 * なお、上記レイヤー別の上書きは挙動は Rebet\Common\Arrays::override($lower_layer, $higher_layer, $option, OverrideOption::PREPEND) と同様の動作をします。
 *
 * @todo i18n 関連の考察
 * @todo 現在の設定をレイヤー別に参照するメソッドなどの実装
 * @todo レイヤー別の設定を配列で取得するメソッドの実装
 *
 * @see Rebet\Config\Configurable
 * @see Rebet\Common\Arrays
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Config
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
    protected static $config = [
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
    public static $option = [
        Layer::LIBRARY     => [],
        Layer::FRAMEWORK   => [],
        Layer::APPLICATION => [],
        Layer::RUNTIME     => [],
    ];

    /**
     * コンパイル済みオプション設定
     *
     * compiled = [
     *   'Section' => [
     *       'key' => value,
     *   ],
     * ]
     *
     * @var array
     */
    protected static $compiled = [];

    /**
     * コンフィグデータを全てクリアします
     */
    public static function clear(?string $section = null) : void
    {
        if ($section === null) {
            foreach ([Layer::LIBRARY, Layer::FRAMEWORK, Layer::APPLICATION, Layer::RUNTIME] as $layer) {
                static::$config[$layer] = [];
                static::$option[$layer] = [];
            }
            static::$compiled = [];
        } else {
            foreach ([Layer::LIBRARY, Layer::FRAMEWORK, Layer::APPLICATION, Layer::RUNTIME] as $layer) {
                unset(static::$config[$layer][$section]);
                unset(static::$option[$layer][$section]);
            }
            unset(static::$compiled[$section]);
        }
    }

    /**
     * 現在の設定内容を全て取得します。
     *
     * ※まだロードされていない未使用のライブラリコンフィグ設定は含まれません
     * ※本メソッドの呼び出しによって ConfigReferrer 経由でロードされたライブラリコンフィグ設定は
     * 　本メソッドの戻り値に含まれないことがあります
     *
     * @return array
     */
    public static function all() : array
    {
        return Reflector::get(static::$compiled, null, []);
    }

    /**
     * 対象セクションのコンフィグ設定をコンパイルします。
     * 本コンパイルは各レイヤー情報の Arrays::override(..., OverrideOption::PREPEND) による上書き設定となります。
     *
     * @param string $section
     * @return void
     */
    protected static function compile(string $section) : void
    {
        $compiled = static::$config[Layer::LIBRARY][$section];
        foreach ([Layer::FRAMEWORK, Layer::APPLICATION, Layer::RUNTIME] as $layer) {
            if (isset(static::$config[$layer][$section])) {
                $compiled = Arrays::override($compiled, static::$config[$layer][$section], static::$option[$layer][$section] ?? [], OverrideOption::PREPEND);
            }
        }

        static::$compiled[$section] = $compiled;
    }

    /**
     * 対象レイヤーのコンフィグを設定／上書きします。
     * 本設定は Arrays::override(..., OverrideOption::PREPEND) による上書き設定となります。
     */
    protected static function put(string $layer, array $config) : void
    {
        $config = self::analyze($config, static::$option[$layer]);
        static::$config[$layer] = Arrays::override(static::$config[$layer], $config, static::$option[$layer], OverrideOption::PREPEND);
        foreach (\array_keys($config) as $section) {
            static::loadLibraryConfig($section);
            static::compile($section);
        }
    }
    
    /**
     * コンフィグ設定を解析します。
     *
     * @param array $config
     * @param array $option
     * @return void
     */
    protected static function analyze(array $config, array &$option)
    {
        if (!\is_array($config) || Arrays::isSequential($config)) {
            return $config;
        }
        
        $analyzed = [];
        foreach ($config as $section => $value) {
            if (\is_array($value) && !Arrays::isSequential($value)) {
                $option[$section] = $option[$section] ?? [] ;
                $value = static::analyzeSection($value, $option[$section]);
            }
            
            $analyzed[$section] = $value;
        }
        return $analyzed;
    }
    
    /**
     * セクション以下のコンフィグ設定を解析します。
     *
     * @param array $config
     * @param array $option
     * @return void
     */
    protected static function analyzeSection(array $config, array &$option)
    {
        if (!\is_array($config) || Arrays::isSequential($config)) {
            return $config;
        }

        $analyzed = [];
        foreach ($config as $key => $value) {
            [$key, $apply_option] = OverrideOption::split($key);
            if ($apply_option !== null) {
                $option[$key] = $apply_option;
            }
            
            if (\is_array($value) && !Arrays::isSequential($value)) {
                $nested_option = [];
                $value = static::analyzeSection($value, $nested_option);
                if ($apply_option === null && !empty($nested_option)) {
                    $option[$key] = $nested_option ;
                }
            }

            $analyzed[$key] = $value;
        }
        return $analyzed;
    }

    /**
     * フレームワークコンフィグを設定します。
     * 本設定は Arrays::override(.., OverrideOption::PREPEND) による上書き設定となります。
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
     * 本設定は Arrays::override(..., OverrideOption::PREPEND) による上書き設定となります。
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
     * 本設定は Arrays::override(..., OverrideOption::PREPEND) による上書き設定となります。
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
    protected static function isDefine(array $config, string $section, ?string $key) : bool
    {
        if (Utils::isBlank($key)) {
            return isset($config[$section]);
        }
        return isset($config[$section]) && Reflector::has($config[$section], $key) ;
    }

    /**
     * アクセスキーの形式をチェックします。
     *
     * @param string|null $key
     * @return void
     * @throws \LogicException
     */
    protected static function validateKey(?string $key) : void
    {
        if (Utils::isBlank($key)) {
            return;
        }
        foreach (\explode('.', $key) as $value) {
            if (\ctype_digit($value)) {
                throw new \LogicException("Invalid config key access, the key '{$key}' contains digit only part.");
            }
        }
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
        static::validateKey($key);

        if (!isset(static::$config[Layer::LIBRARY][$section])) {
            static::loadLibraryConfig($section);
            static::compile($section);
        }
        
        $value = Reflector::get(static::$compiled[$section], $key);
        if ($required && Utils::isBlank($value)) {
            throw new ConfigNotDefineException("Required config {$section}".($key ? "#{$key}" : "")." is blank or not define.");
        }
        return $value ?? $default;
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
        static::$config[Layer::LIBRARY][$section] = method_exists($section, 'defaultConfig') ? static::analyze($section::defaultConfig(), static::$option[Layer::LIBRARY][$section]) : [] ;
    }

    /**
     * コンフィグの設定値からインスタンスを生成します。
     *
     * @see Rebet\Common\Reflector::instantiate()
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
        return Reflector::instantiate(self::get($section, $key, $required, $default));
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
        static::validateKey($key);
        
        if (!isset(static::$config[Layer::LIBRARY][$section])) {
            static::loadLibraryConfig($section);
            static::compile($section);
        }

        return static::isDefine(static::$compiled, $section, $key);
    }

    /**
     * 他のセクションのコンフィグ設定を共有するリファラを返します。
     *
     * ex)
     * // 日時に関連したクラスでの定義例
     * public static function defaultConfig() {
     *     return [
     *         'default_format'   => 'Y-m-d H:i:s',
     *         'default_timezone' => Config::refer(Other::class, 'timezone', date_default_timezone_get() ?: 'UTC'),
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
        static::validateKey($key);
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
