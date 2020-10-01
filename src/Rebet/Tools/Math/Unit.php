<?php
namespace Rebet\Tools\Math;

use Rebet\Tools\Arrays;
use Rebet\Tools\Callback;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Reflection\Reflector;

/**
 * Unit Class
 *
 * @todo Define units borrowed from https://en.wikipedia.org/wiki/Conversion_of_units
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Unit
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            // Unit factors definition.
            // 'factors' => [
            //     'unit_name' => [
            //         'symbole' => ['factor', 'auto_scalable'],
            //     ],
            // ]
            //
            // @see https://en.wikipedia.org/wiki/International_System_of_Units
            // @see https://en.wikipedia.org/wiki/International_System_of_Units#Non-SI_units_accepted_for_use_with_SI
            // @see https://en.wikipedia.org/wiki/Metric_prefix
            // @see https://en.wikipedia.org/wiki/Conversion_of_units
            'factors' => [
                // The International System of Units Prefix (Metric prefix) Symbol factors.
                UNIT::SI_PREFIX => [
                    'Y'  => ['1e24' , true ], // yotta
                    'Z'  => ['1e21' , true ], // zetta
                    'E'  => ['1e18' , true ], // exa
                    'P'  => ['1e15' , true ], // peta
                    'T'  => ['1e12' , true ], // tera
                    'G'  => ['1e9'  , true ], // giga
                    'M'  => ['1e6'  , true ], // mega
                    'k'  => ['1e3'  , true ], // kilo
                    'h'  => ['1e2'  , false], // hecto
                    'da' => ['1e1'  , false], // deca
                    ''   => ['1'    , true ], // (Base Prefix)
                    'd'  => ['1e-1' , false], // deci
                    'c'  => ['1e-2' , false], // centi
                    'm'  => ['1e-3' , true ], // milli
                    'μ'  => ['1e-6' , true ], // micro
                    'n'  => ['1e-9' , true ], // nano
                    'p'  => ['1e-12', true ], // pico
                    'f'  => ['1e-15', true ], // femto
                    'a'  => ['1e-18', true ], // atto
                    'z'  => ['1e-21', true ], // zepto
                    'y'  => ['1e-24', true ], // yocto
                ],

                // The Binary Prefix defined in IEEE 1541-2002 Symbol factors.
                UNIT::BINARY_PREFIX => [
                    'Yi' => [bcpow(2, 80), true], // yobi
                    'Zi' => [bcpow(2, 70), true], // zebi
                    'Ei' => [bcpow(2, 60), true], // exbi
                    'Pi' => [bcpow(2, 50), true], // pebi
                    'Ti' => [bcpow(2, 40), true], // tebi
                    'Gi' => [bcpow(2, 30), true], // gibi
                    'Mi' => [bcpow(2, 20), true], // mebi
                    'Ki' => [bcpow(2, 10), true], // kibi
                    ''   => ['1'         , true], // (Base Prefix)
                ],

                // Custom Binary Prefixes factors that omitted 'i' from symbols.
                UNIT::STORAGE_PREFIX => [
                    'Y' => [bcpow(2, 80), true], // yotta
                    'Z' => [bcpow(2, 70), true], // zetta
                    'E' => [bcpow(2, 60), true], // exa
                    'P' => [bcpow(2, 50), true], // peta
                    'T' => [bcpow(2, 40), true], // tera
                    'G' => [bcpow(2, 30), true], // giga
                    'M' => [bcpow(2, 20), true], // mega
                    'K' => [bcpow(2, 10), true], // kilo
                    ''  => ['1'         , true], // (Base Prefix)
                ],

                // Time Units include Non-SI units accepted for use with SI and useful units.
                // @see https://en.wikipedia.org/wiki/Conversion_of_units#Time
                UNIT::TIME => [
                    'c'   => ['3153600000'       , false], // Century       [≡100y]
                    'dec' => ['315360000'        , false], // Decade        [≡10y]
                    'y'   => ['31536000'         , false], // Year (common) [≡365d]
                    'mo'  => ['1209600'          , false], // Month (full)  [≡30d]
                    'fn'  => ['1209600'          , false], // Fortnight     [≡2wk]
                    'wk'  => ['604800'           , false], // Week
                    'd'   => ['86400'            , true ], // Day
                    'h'   => ['3600'             , true ], // Hour
                    'min' => ['60'               , true ], // Minute
                    's'   => ['1'                , true ], // Second (Base Unit)
                    'j'   => [bcdiv('1', '60', 9), false], // Jiffy
                    'ja'  => ['0.01'             , false], // Jiffy (alternative)
                    'ms'  => ['0.001'            , true ], // Milli Second
                    'μs'  => ['0.000001'         , true ], // Micro Second
                    'au'  => ['2.418884254e-17'  , false], // Atomic unit of time
                    'tp'  => ['5.39116e-44'      , false], // Planck time
                ],

                // Length Units include Non-SI units accepted for use with SI and useful units.
                // @see https://en.wikipedia.org/wiki/Conversion_of_units#Length
                UNIT::LENGTH => [
                    'pc'   => ['30856775814913700' , false], // parsec
                    'ly'   => ['9.4607304725808e15', false], // light-year
                    'AU'   => ['149597870700'      , true ], // astronomical unit
                    'Gm'   => ['1e9'               , false], // gigameter
                    'Mm'   => ['1e6'               , false], // megameter
                    'lea'  => ['4828'              , false], // league (land)
                    'nmi'  => ['1852'              , false], // nautical mile (international) [≡ 1852m]
                    'mi'   => ['1609.344'          , false], // mile (international) [≡ 80ch ≡ 5280ft ≡ 1760yd]
                    'km'   => ['1e3'               , true ], // kilometer
                    'fur'  => ['201.168'           , false], // furlong [≡ 10ch = 660ft = 220yd]
                    'ch'   => ['20.11684'          , false], // chain (Gunter's; Surveyor's) [≡ 66ft(US) ≡ 4rods]
                    'rope' => ['6.096'             , false], // rope (H) [≡ 20ft]
                    'rd'   => ['5.0292'            , false], // rod; pole; perch (H) [≡ ​16 1/2ft]
                    'ell'  => ['1.143'             , false], // ell (H) [≡ 45in (In England usually)]
                    'ftm'  => ['1.8288'            , false], // fathom [≡ 6ft]
                    'm'    => ['1'                 , true ], // meter (Base Unit)
                    'yd'   => ['0.9144'            , false], // yard (International) [≡ 0.9144m ≡ 3ft ≡ 36in]
                    'lnk'  => ['0.2011684'         , false], // link (Gunter's; Surveyor's) [≡ 1/100ch ≡ 0.66ft(US) ≡ 7.92in]
                    'ft'   => ['0.3048'            , false], // foot (International) [≡ 0.3048m ≡ ​1/3yd ≡ 12inches]
                    'in'   => ['0.0254'            , false], // inch (International) [≡ 2.54cm ≡ ​1/36yd ≡ ​1/12ft]
                    'cal'  => ['0.0254'            , false], // calibre [≡ 1in]
                    'dm'   => ['1e-1'              , false], // decimetre
                    'cm'   => ['1e-2'              , true ], // centimetre
                    'ln'   => ['0.002116666667'    , false], // line
                    'mm'   => ['1e-3'              , true ], // millimeter
                    'twp'  => ['1.763888889e-5'    , false], // twip [≡ ​1/1440in]
                    'μm'   => ['1e-6'              , true ], // micrometre (old: micron)
                    'nm'   => ['1e-9'              , true ], // nanometre
                    'Å'    => ['1e-10'             , false], // ångström
                    'a0'   => ['5.2917721092e-11'  , false], // bohr, atomic unit of length
                    'pm'   => ['1e-12'             , true ], // picometre
                    'xu'   => ['1.0021e-13'        , false], // x unit; siegbahn
                    'fm'   => ['1e-15'             , true ], // femtometre/fermi
                    'am'   => ['1e-18'             , true ], // attometre
                    'zm'   => ['1e-21'             , true ], // yoctometre
                    'ym'   => ['1e-24'             , true ], // zeptometre
                    'lp'   => ['1.61624e-35'       , false], // Planck length
                ],

                // Mass Units include Non-SI units accepted for use with SI and useful units.
                // @see https://en.wikipedia.org/wiki/Conversion_of_units#Mass
                UNIT::MASS => [
                    'Yg'  => ['1e21'              , false], // yottagram
                    'Pt'  => ['1e18'              , true ], // petatonne
                    'Zg'  => ['1e18'              , false], // zettagram
                    'Tt'  => ['1e15'              , true ], // teratonne
                    'Eg'  => ['1e15'              , false], // yotagram
                    'Gt'  => ['1e12'              , true ], // gigatonne
                    'Pg'  => ['1e12'              , false], // petagram
                    'Mt'  => ['1e9'               , true ], // megatonne
                    'Tg'  => ['1e9'               , false], // teragram
                    'kt'  => ['1e6'               , true ], // kilotonne
                    'Gg'  => ['1e6'               , false], // gigagram
                    't'   => ['1e3'               , true ], // tonne (mts unit)
                    'Mg'  => ['1e3'               , false], // megagram
                    'kip' => ['453.59237'         , false], // kip [≡ 1000lb av ]
                    'st'  => ['6.35029318'        , false], // stone
                    'kg'  => ['1'                 , true ], // kilogram (Base Unit)
                    'lb'  => ['0.45359237'        , false], // pound (avoirdupois)
                    'dr'  => ['1.7718451953125e-3', false], // dram (avoirdupois) [≡ ​27 11/32 gr]
                    'oz'  => ['28.349523125e-3'   , false], // ounce (avoirdupois)
                    'g'   => ['1e-3'              , true ], // gram
                    'gr'  => ['64.79891e-6'       , false], // grain
                    'mg'  => ['1e-6'              , true ], // milligram
                    'ct'  => ['200e-6'            , false], // carat (metric) [≡ 200mg]
                    'kt'  => ['205.1965483e-6'    , false], // carat [≡ ​3 1/6gr]
                    'μg'  => ['1e-9'              , true ], // microgram
                    'γ'   => ['1e-9'              , false], // gamma [≡ 1μg]
                    'ng'  => ['1e-12'             , true ], // nanogram
                    'pg'  => ['1e-15'             , true ], // picogram
                    'fg'  => ['1e-18'             , true ], // femtogram
                    'ag'  => ['1e-21'             , true ], // attogram
                    'zg'  => ['1e-24'             , true ], // zeptogram
                    'Da'  => ['1.66053906660e-27' , false], // dalton
                    'yg'  => ['1e-27'             , true ], // yoctogram
                    'AMU' => ['1.660539040e-27'   , false], // atomic mass unit, unified
                    'eV'  => ['1.78266184e-36'    , false], // electronvolt
                ],

                // Electric Current Units include Non-SI units accepted for use with SI and useful units.
                // @see https://en.wikipedia.org/wiki/Conversion_of_units#Electric_current
                UNIT::ELECTRIC_CURRENT => [
                    'YA'    => ['1e24'         , true ], // yottaampere
                    'ZA'    => ['1e21'         , true ], // zettaampere
                    'EA'    => ['1e18'         , true ], // exaampere
                    'PA'    => ['1e15'         , true ], // petaampere
                    'TA'    => ['1e12'         , true ], // teraampere
                    'GA'    => ['1e9'          , true ], // gigaampere
                    'MA'    => ['1e6'          , true ], // megaampere
                    'kA'    => ['1e3'          , true ], // kiloampere
                    'abamp' => ['10'           , false], // electromagnetic unit; abampere (cgs unit)
                    'A'     => ['1'            , true ], // ampere (Base Unit)
                    'mA'    => ['1e-3'         , true ], // miliampere
                    'μA'    => ['1e-6'         , true ], // microampere
                    'nA'    => ['1e-9'         , true ], // nanoampere
                    'esu/s' => ['3.335641e-10' , false], // esu per second; statampere (cgs unit)
                    'pA'    => ['1e-12'        , true ], // picoampere
                    'fA'    => ['1e-15'        , true ], // femtoampere
                    'aA'    => ['1e-18'        , true ], // attoampere
                    'zA'    => ['1e-21'        , true ], // zepttoampere
                    'yA'    => ['1e-24'        , true ], // yopttoampere
                ],

                // Temperature Units include Non-SI units accepted for use with SI and useful units.
                // @see https://en.wikipedia.org/wiki/Conversion_of_units#Temperature
                UNIT::TEMPERATURE => [
                    'YK' => ['1e24' , true ], // yottakelvin
                    'ZK' => ['1e21' , true ], // zettakelvin
                    'EK' => ['1e18' , true ], // exakelvin
                    'PK' => ['1e15' , true ], // petakelvin
                    'TK' => ['1e12' , true ], // terakelvin
                    'GK' => ['1e9'  , true ], // gigakelvin
                    'MK' => ['1e6'  , true ], // megakelvin
                    'kK' => ['1e3'  , true ], // kilokelvin
                    'K'  => ['1'    , true ], // kelvin (Base Unit)
                    'mK' => ['1e-3' , true ], // milikelvin
                    'μK' => ['1e-6' , true ], // microkelvin
                    'nK' => ['1e-9' , true ], // nanokelvin
                    'pK' => ['1e-12', true ], // picokelvin
                    'fK' => ['1e-15', true ], // femtokelvin
                    'aK' => ['1e-18', true ], // attokelvin
                    'zK' => ['1e-21', true ], // zepttokelvin
                    'yK' => ['1e-24', true ], // yopttokelvin
                    '°C' => [[                // Celsius
                        'from_base' => function (Decimal $value) { return $value->sub('273.15'); },
                        'to_base'   => function (Decimal $value) { return $value->add('273.15'); },
                    ], false],
                    '°F' => [[                // Fahrenheit
                        'from_base' => function (Decimal $value) { return $value->mul('9.0000')->div('5.0000')->sub('459.67'); },
                        'to_base'   => function (Decimal $value) { return $value->add('459.67')->mul('5.0000')->div('9.0000'); },
                    ], false],
                    '°R'  => ['1.8', false],  // Rankine
                    '°De' => [[               // Delisle
                        'from_base' => function (Decimal $value) { return Decimal::of('373.15')->sub($value)->mul('3.0000')->div('2.0000'); },
                        'to_base'   => function (Decimal $value) { return Decimal::of('373.15')->sub($value->mul('2.0000')->div('3.0000')); },
                    ], false],
                    '°N' => [[                // Newton
                        'from_base' => function (Decimal $value) { return $value->sub('273.15')->mul('33.000')->div('100.00'); },
                        'to_base'   => function (Decimal $value) { return $value->mul('100.00')->div('33.000')->add('273.15'); },
                    ], false],
                    '°Ré' => [[               // Réaumur
                        'from_base' => function (Decimal $value) { return $value->sub('273.15')->mul('4.0000')->div('5.0000'); },
                        'to_base'   => function (Decimal $value) { return $value->mul('5.0000')->div('4.0000')->add('273.15'); },
                    ], false],
                    '°Rø' => [[               // Rømer
                        'from_base' => function (Decimal $value) { return $value->sub('273.15')->mul('21.000')->div('40.000')->add('7.5000'); },
                        'to_base'   => function (Decimal $value) { return $value->sub('7.5000')->mul('40.000')->div('21.000')->add('273.15'); },
                    ], false],
                ],
            ],
            'options' => [
                'omit_zero'           => true,
                'without_prefix'      => false,
                'before_prefix'       => '',
                'after_prefix'        => '',
                'decimal_point'       => '.',
                'thousands_separator' => ',',
            ],
        ];
    }

    /**
     * @var string The International System of Units Prefix (Metric prefix) Symbol factors.
     */
    const SI_PREFIX = 'si_prefix';

    /**
     * @var string The Binary Prefix defined in IEEE 1541-2002 Symbol factors.
     */
    const BINARY_PREFIX = 'binary_prefix';

    /**
     * @var string Custom Binary Prefixes factors that omitted 'i' from symbols.
     */
    const STORAGE_PREFIX = 'storage_prefix';

    /**
     * @var string Time Units include Non-SI units accepted for use with SI and useful units.
     */
    const TIME = 'time';

    /**
     * @var string Length Units include Non-SI units accepted for use with SI and useful units.
     */
    const LENGTH = 'length';

    /**
     * @var string Mass Units include Non-SI units accepted for use with SI and useful units.
     */
    const MASS = 'mass';

    /**
     * @var string Electric Current Units include Non-SI units accepted for use with SI and useful units.
     */
    const ELECTRIC_CURRENT = 'electric_current';

    /**
     * @var string Temperature Units include Non-SI units accepted for use with SI and useful units.
     */
    const TEMPERATURE = 'temperature';

    /**
     * @var array of Unit factors
     */
    protected $units = [];

    /**
     * @var array of Options
     */
    protected $options = [];

    /**
     * Get unit factors definition.
     *
     * @param string $name
     * @param bool $safety convert using library default configure. (default: false)
     * @return array
     */
    public static function factorsOf(string $name, bool $safety = false) : array
    {
        return $safety ? Reflector::get(static::defaultConfig(), "factors.{$name}", []) : static::config("factors.{$name}", false, []);
    }

    /**
     * Get base unit symbol from given units.
     *
     * @param array|string $units
     * @return string|null
     */
    public static function baseUnitOf($units) : ?string
    {
        $units = is_array($units) ? $units : static::factorsOf($units) ;
        return Arrays::find($units, function ($v) { return $v[0] === '1'; });
    }

    /**
     * Create Unit Converter of given name or unit factors.
     *
     * @param array|string $name
     * @param array $options (default: depend on configure)
     *     - omit_zero            : true
     *     - without_prefix       : false
     *     - before_prefix        : ''
     *     - after_prefix         : ''
     *     - decimal_point        : '.'
     *     - thousands_separator  : ','
     * @param bool $safety convert using library default configure. (default: false)
     * @return self
     */
    public static function of($name, array $options = [], bool $safety = false) : self
    {
        return new static($name, $options, $safety);
    }

    /**
     * Create Unit Converter of given name or unit factors.
     *
     * @param array|string $units name or array of unit factors
     * @param array $options (default: depend on configure)
     *     - omit_zero            : true
     *     - without_prefix       : false
     *     - before_prefix        : ''
     *     - after_prefix         : ''
     *     - decimal_point        : '.'
     *     - thousands_separator  : ','
     * @param bool $safety convert using library default configure. (default: false)
     */
    public function __construct($units, array $options = [], bool $safety = false)
    {
        $this->units   = is_array($units) ? $units : static::factorsOf($units, $safety) ;
        $this->options = array_merge(static::config('options'), $options);
    }

    /**
     * Exchange and format from the given value with/without unit prefix to given unit prefix format.
     *
     * @param int|float|string|Decimal $value can be contains unit.
     * @param string|null $to prefix name to exchange. If the null given then exchange to human readable. (default: null)
     * @param int|null $precision (default: 2).
     * @param array $options for runtime override (default: [])
     * @return string
     */
    public function exchange($value, ?string $to = null, ?int $precision = 2, array $options = []) : string
    {
        extract($options = array_merge($this->options, $options));
        $units       = Arrays::sortKeys($this->units, SORT_DESC, Callback::compareLength());
        $value       = is_string($value) ? $value : Decimal::of($value)->value() ;
        $from_factor = '1';
        foreach ($units as $prefix => [$factor, /*auto_scaleable*/]) {
            if (preg_match('/'.preg_quote($before_prefix.$prefix.$after_prefix, '/').'$/', $value)) {
                $from_factor = is_array($factor) ? $factor['to_base'] : $factor ;
                break;
            }
        }
        $number = Decimal::of(str_replace([$before_prefix, $after_prefix], '', str_replace(array_keys($units), '', $value)), $decimal_point, $thousands_separator);
        $number = is_callable($from_factor) ? call_user_func($from_factor, $number) : $number->mul($from_factor) ;
        if ($to !== null) {
            $to_factor = $units[$to][0] ?? 1;
            $to_factor = is_array($to_factor) ? $to_factor['from_base'] : $to_factor ;
        } else {
            $abs_number = $number->abs();
            foreach (Arrays::sort(Arrays::where($units, function ($flactor) { return $flactor[1]; }), SORT_ASC, function ($a, $b) { return Decimal::of($a[0])->comp($b[0]); }) as $prefix => [$factor, $auto_scalable]) {
                if ($to === null) {
                    $to_factor = $factor;
                    $to        = $prefix;
                }
                if ($abs_number->lt($factor)) {
                    break;
                }
                $to        = $prefix;
                $to_factor = $factor;
            }
        }
        $result = is_callable($to_factor) ? call_user_func($to_factor, $number) : $number->div($to_factor) ;
        return ($precision === null ? $result->normalize() : $result->round($precision))->format($omit_zero, $decimal_point, $thousands_separator) . (empty($to) || $without_prefix ? '' : $before_prefix.$to.$after_prefix);
    }

    /**
     * Parse the given value with/without unit prefix to given unit prefix value.
     *
     * @param int|float|string $value can be contains unit.
     * @param string $to prefix name to convert. (default: null for base unit)
     * @param array $options for runtime override (default: [])
     * @return Decimal
     */
    public function convert($value, ?string $to = null, array $options = []) : Decimal
    {
        $to      = $to ?? static::baseUnitOf($this->units);
        $options = array_merge($this->options, $options, ['without_prefix' => true]);
        return Decimal::of($this->exchange($value, $to, null, $options), $options['decimal_point'] ?? '.', $options['thousands_separator'] ?? ',');
    }
}
