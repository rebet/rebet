<?php

use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Math\Unit;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Resource\Resource;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Tinker\Tinker;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Env;
use Rebet\Tools\Utility\Namespaces;
use Rebet\Tools\Utility\Securities;
use Rebet\Tools\Utility\Strings;
use Rebet\Tools\Utility\Utils;

return [
    DateTime::class => [
        'default_format'             => 'Y-m-d H:i:s',
        'default_timezone'           => date_default_timezone_get() ?: 'UTC',
        'acceptable_datetime_format' => [
            'Y-m-d H:i:s.u',
            'Y-m-d H:i:s',
            'Y/m/d H:i:s',
            'YmdHis',
            'Y-m-d H:i',
            'Y/m/d H:i',
            'YmdHi',
            'Y-m-d',
            'Y/m/d',
            'Ymd',
        ],
        'test_now'                   => null,
        'test_now_timezone'          => null,
        'test_now_format'            => ['Y#m#d H:i:s.u', 'Y#m#d H:i:s', 'Y#m#d H:i', 'Y#m#d'],
        'custom_formats'             => [
            'xwww' => function (DateTime $datetime) { return $datetime->getDayOfWeek()->translate('label'); },
            'xww'  => function (DateTime $datetime) { return $datetime->getDayOfWeek()->translate('label_short'); },
            'xw'   => function (DateTime $datetime) { return $datetime->getDayOfWeek()->translate('label_min'); },
            'xmmm' => function (DateTime $datetime) { return $datetime->getLocalizedMonth()->translate('label'); },
            'xmm'  => function (DateTime $datetime) { return $datetime->getLocalizedMonth()->translate('label_short'); },
            'xa'   => function (DateTime $datetime) { return $datetime->getMeridiem(false); },
            'xA'   => function (DateTime $datetime) { return $datetime->getMeridiem(true); },
        ],
    ],

    Decimal::class => [
        'mode'    => Decimal::MODE_AUTO_PRECISION_SCALING,
        'options' => [
            'fixed_scale'  => 2,  // For MODE_FIXED_DECIMAL_PLACES
            'guard_digits' => 4,  // For MODE_FIXED_DECIMAL_PLACES / MODE_SIGNIFICANCE_ARITHMETIC
            'max_scale'    => 90, // For MODE_AUTO_PRECISION_SCALING
        ],
    ],

    Unit::class => [
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
    ],

    Resource::class => [
        'loader' => [
            'php'  => function (string $path, array $option) {
                if (!\file_exists($path)) {
                    return null;
                }
                $resource = require $path;
                return is_array($resource) ? $resource : [] ;
            },
            'json' => function (string $path, array $option) {
                if (!\file_exists($path)) {
                    return null;
                }
                return \json_decode(\file_get_contents($path), true);
            },
            'ini'  => function (string $path, array $option) {
                if (!\file_exists($path)) {
                    return null;
                }
                return \parse_ini_file($path, $option['process_sections'] ?? true, $option['scanner_mode'] ?? INI_SCANNER_TYPED);
            },
            'txt'  => function (string $path, array $option) {
                if (!\file_exists($path)) {
                    return null;
                }
                return \explode($option['delimiter'] ?? "\n", \file_get_contents($path));
            },
        ]
    ],

    System::class => [
        'emulators' => [
            'header' => [
                'emulator' => function (string $header, bool $replace = true, int $http_response_code = null) {
                    $emulated_header = &System::memory('emulated_header');
                    $http_status     = System::datasets('header', 'http_status');
                    if (\preg_match('/^HTTP\//', $header)) {
                        $emulated_header['http'] = [$header];
                    } elseif ($http_response_code !== null && isset($http_status[$http_response_code])) {
                        $emulated_header['http'] = ["HTTP/1.1 {$http_response_code} ".$http_status[$http_response_code]];
                    } elseif (!isset($emulated_header['http'])) {
                        $emulated_header['http'] = ['HTTP/1.1 200 OK'];
                    }

                    if (\strpos($header, ':') !== false) {
                        $parts = \explode(':', $header, 2);
                        $key   = \strtolower($parts[0]);
                        if (!isset($emulated_header[$key])) {
                            $emulated_header[$key] = [];
                        }
                        if ($replace) {
                            $emulated_header[$key] = [$header];
                        } else {
                            $emulated_header[$key][] = $header;
                        }
                    }
                },
                'datasets' => [
                    'http_status' => [
                        100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
                        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used',
                        300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
                        400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Payload Too Large', 414 => 'URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable', 417 => 'Expectation Failed', 418 => "I'm a teapot", 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 426 => 'Upgrade Required', 451 => 'Unavailable For Legal Reasons',
                        500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 509 => 'Bandwidth Limit Exceeded', 510 => 'Not Extended', 511 => 'Network Authentication Required',
                    ]
                ],
            ],
            'headers_list' => [
                'emulator' => function () {
                    return Arrays::flatten(\array_values(System::memory('emulated_header')));
                },
            ],
            'dns_get_record' => [
                'emulator' => function (string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false) : array {
                    $emulated_dns = System::datasets('dns_get_record', 'emulated_dns');
                    if (isset($emulated_dns[$hostname])) {
                        $c = Tinker::with($emulated_dns[$hostname], true);
                        return array_values($c->where(function ($v) use ($type) {
                            $vt = Reflector::get($v, 'type');
                            switch (true) {
                                case DNS_ANY === $type: return true;
                                case DNS_ALL === $type: return true;
                                case DNS_A & $type && $vt === 'A': return true;
                                case DNS_CNAME & $type && $vt === 'CNAME': return true;
                                case DNS_HINFO & $type && $vt === 'HINFO': return true;
                                // case DNS_CAA & $type && $vt === 'CAA': return true; // PHP Warning:  Use of undefined constant DNS_CAA - assumed 'DNS_CAA' (this will throw an Error in a future version of PHP)
                                case DNS_MX & $type && $vt === 'MX': return true;
                                case DNS_NS & $type && $vt === 'NS': return true;
                                case DNS_PTR & $type && $vt === 'PTR': return true;
                                case DNS_SOA & $type && $vt === 'SOA': return true;
                                case DNS_TXT & $type && $vt === 'TXT': return true;
                                case DNS_AAAA & $type && $vt === 'AAAA': return true;
                                case DNS_SRV & $type && $vt === 'SRV': return true;
                                case DNS_NAPTR & $type && $vt === 'NAPTR': return true;
                                case DNS_A6 & $type && $vt === 'A6': return true;
                                case DNS_NAPTR & $type && $vt === 'NAPTR': return true;
                            }
                            return false;
                        })->return());
                    }
                    return [];
                },
                'datasets' => [
                    'emulated_dns' => [
                        'sample.local' => [
                            ["host" => "sample.local", "class" => "IN", "ttl" => 60  , "type" => "A", "ip" => "127.0.0.1"],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 1, "target" => "mx.sample.local"],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt1.mx.sample.local"],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt2.mx.sample.local"],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "v=spf1 mx ~all", "entries" => ["v=spf1 mx ~all"]],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "SOA", "mname" => "ns1.p01.dynect.net", "rname" => "hostmaster.sample.local", "serial" => 1234567890, "refresh" => 3600, "retry" => 600, "expire" => 604800, "minimum-ttl" => 60],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns1.p01.dynect.net"],
                            ["host" => "sample.local", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns2.p01.dynect.net"],
                        ],
                    ]
                ],
            ],
        ],
    ],

    Tinker::class => [
        'filter' => [
            'delegaters' => [
                Reflector::class => ['convert'],
                Utils::class     => ['isBlank', 'bvl', 'isEmpty', 'evl'],
                Strings::class   => ['lcut', 'rcut', 'clip', 'indent', 'ltrim', 'rtrim', 'trim', 'mbtrim', 'startsWith', 'endsWith', 'contains', 'match', 'wildmatch', 'split'],
                Arrays::class    => [
                    'pluck', 'override', 'duplicate', 'crossJoin', 'only', 'except', 'where', 'compact', 'unique',
                    'first', 'last', 'flatten', 'prepend', 'shuffle', 'map', 'reduce', 'diff', 'intersect',
                    'every', 'groupBy', 'union', 'min', 'max', 'sort', 'sortBy', 'sortKeys', 'sum', 'avg',
                    'median', 'mode', 'implode', 'toQuery'
                ],
            ],
            'customs' => [
                // You can use php built-in functions as filters when the 1st argument is for value.
                'nvl'      => function ($value, $default) { return $value ?? $default; },
                'default'  => function ($value, $default) { return $value ?? $default; },
                'escape'   => function (string $value, string $type = 'html') {
                    switch ($type) {
                        case 'html': return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        case 'url': return urlencode($value);
                        default: throw new \InvalidArgumentException("Invalid escape type [{$type}] given. The type must be html or url");
                    }
                },
                'nl2br'     => function (string $value) { return nl2br($value); },
                'datetimef' => function (DateTime $value, string $format) { return $value->format($format); },
                'numberf'   => function ($value, int $precision = 0, bool $omit_zero = false, string $decimal_point = '.', string $thousands_separator = ',') { return $value === null ? null : Decimal::of($value)->round($precision)->format($omit_zero, $decimal_point, $thousands_separator); },
                'stringf'   => function ($value, string $format) { return $value === null ? null : sprintf($format, $value) ; },
                'explode'   => function (string $value, string $delimiter, int $limit = PHP_INT_MAX) { return explode($delimiter, $value, $limit); },
                'replace'   => function (string $value, $pattern, $replacement, int $limit = -1) { return preg_replace($pattern, $replacement, $value, $limit); },
                'lower'     => function (string $value) { return strtolower($value); },
                'upper'     => function (string $value) { return strtoupper($value); },
                'decimal'   => function ($value) { return $value === null ? null : Decimal::of($value); },
                'abs'       => function ($value) { return $value === null ? null : Decimal::of($value)->abs(); },
                'eq'        => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->eq($other, $precision); },
                'gt'        => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->gt($other, $precision); },
                'gte'       => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->gte($other, $precision); },
                'lt'        => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->lt($other, $precision); },
                'lte'       => function ($value, $other, ?int $precision = null) { return $value === null || $other === null ? false : Decimal::of($value)->lte($other, $precision); },
                'add'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->add($other, $precision, $mode); },
                'sub'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->sub($other, $precision, $mode); },
                'mul'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->mul($other, $precision, $mode); },
                'div'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->div($other, $precision, $mode); },
                'pow'       => function ($value, $other, ?int $precision = null, ?int $mode = null) { return $value === null || $other === null ? null : Decimal::of($value)->pow($other, $precision, $mode); },
                'sqrt'      => function ($value, ?int $precision = null, ?int $mode = null) { return $value === null ? null : Decimal::of($value)->sqrt($precision, $mode); },
                'mod'       => function ($value, $modulus) { return $value === null || $modulus === null ? null : Decimal::of($value)->mod($modulus); },
                'powmod'    => function ($value, $exponent, $modulus) { return $value === null || $exponent === null || $modulus === null ? null : Decimal::of($value)->powmod($exponent, $modulus); },
                'floor'     => function ($value, int $precision = 0) { return $value === null ? null : Decimal::of($value)->floor($precision); },
                'round'     => function ($value, int $precision = 0, int $guard_digits = 0, int $precision_type = Decimal::TYPE_DECIMAL_PLACES) { return $value === null ? null : Decimal::of($value)->round($precision, $guard_digits, $precision_type); },
                'ceil'      => function ($value, int $precision = 0) { return $value === null ? null : Decimal::of($value)->ceil($precision); },
                'dump'      => function ($value, array $masks = [], string $masked_label = '********') { return Strings::stringify($value, $masks, $masked_label); },
                'invoke'    => function ($value, ...$args) { return call_user_func($value, ...$args); },
                'equals'    => function ($value, $other) { return $value == $other; },
                'sameAs'    => function ($value, $other) { return $value === $other; },
                'nnvl'      => function ($value, $then, $else = null) { return $value !== null ? $then : $else ; },
                'nbvl'      => function ($value, $then, $else = null) { return !Utils::isBlank($value) ? $then : $else; },
                'nevl'      => function ($value, $then, $else = null) { return !Utils::isEmpty($value) ? $then : $else; },
                'when'      => function ($value, $test, $then, $else = null) {
                    $test = static::peel($test);
                    $test = is_callable($test) ? call_user_func($test, $value) : $test ;
                    return  (is_bool($test) ? $test : $value === $test) ? $then : ($else ?? $value) ;
                },
                'case'      => function ($value, array $map, $default = null) { return $map[$value] ?? $default ?? $value; },
                'length'    => function ($value) {
                    switch (true) {
                        case $value === null:    return null;
                        case is_numeric($value): return mb_strlen((string)$value);
                        case is_string($value):  return mb_strlen($value);
                    }
                    return Arrays::count($value);
                },
                'values'    => function (array $value) { return array_values($value); },
                'keys'      => function (array $value) { return array_keys($value); },
            ],
        ],
    ],

    FileDictionary::class => [
        'resources' => [
            // 'i18n' => FileDictionary::$resouce_dirs,
        ],
    ],

    Translator::class => [
        'dictionary'      => FileDictionary::class,
        'resource_adder'  => [
            FileDictionary::class => function (FileDictionary $dictionary, ...$args) { $dictionary->addLibraryResource(...$args); },
        ],
        'locale'          => locale_get_default(),
        'fallback_locale' => 'en',
        'ordinalize'      => [
            'en' => function (int $num) {
                return in_array($num % 100, [11, 12, 13]) ? $num.'th' : $num.(['th', 'st', 'nd', 'rd'][$num % 10] ?? 'th');
            },
        ],
    ],

    Namespaces::class => [
        'aliases' => [],
    ],

    Securities::class => [
        'hash' => [
            'salt'       => Env::promise('DEFAULT_HASH_SALT'),
            'pepper'     => Env::promise('DEFAULT_HASH_PEPPER'),
            'algorithm'  => 'SHA256',
            'stretching' => 1000,
        ],
        'crypto' => [
            'secret_key' => Env::promise('DEFAULT_SECRET_KEY'),
            'cipher'     => 'AES-256-CBC',
        ],
    ],
];
