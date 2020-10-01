<?php
namespace Rebet\Tests\Tools;

use Rebet\Tools\Decimal;
use Rebet\Tools\Unit;
use Rebet\Config\Config;
use Rebet\Tests\RebetTestCase;

class UnitTest extends RebetTestCase
{
    public function test_factorsOf()
    {
        $factors = Unit::factorsOf(UNIT::TIME);
        $this->assertTrue(array_key_exists('c', $factors));
        $this->assertTrue(array_key_exists('min', $factors));
        $this->assertTrue(array_key_exists('μs', $factors));

        $this->assertSame('3153600000', $factors['c'][0]);

        Config::application([
            Unit::class => [
                'factors' => [
                    Unit::TIME => [
                        'c' => ['3.1536e9', false],
                    ]
                ]
            ]
        ]);

        $factors = Unit::factorsOf(UNIT::TIME);
        $this->assertSame('3.1536e9', $factors['c'][0]);
        $factors = Unit::factorsOf(UNIT::TIME, true);
        $this->assertSame('3153600000', $factors['c'][0]);
    }

    public function test_baseUnitOf()
    {
        $this->assertSame('s', Unit::baseUnitOf(Unit::TIME));
        $this->assertSame('K', Unit::baseUnitOf(Unit::TEMPERATURE));
        $this->assertSame('kg', Unit::baseUnitOf(Unit::MASS));
        $this->assertSame('a', Unit::baseUnitOf([
            'a' => ['1', true],
            'b' => ['2', true],
            'c' => ['3', true],
            'D' => ['4', true],
        ]));
        $this->assertSame(null, Unit::baseUnitOf([
            'a' => ['10', true],
            'b' => ['20', true],
            'c' => ['30', true],
            'D' => ['40', true],
        ]));
    }

    public function dataExchanges() : array
    {
        return [
            ['0.12y' , UNIT::SI_PREFIX, 0.000000000000000000000000123],
            ['1.23y' , UNIT::SI_PREFIX, 0.00000000000000000000000123],
            ['1.23z' , UNIT::SI_PREFIX, 0.00000000000000000000123],
            ['1.23a' , UNIT::SI_PREFIX, 0.00000000000000000123],
            ['-1.23f', UNIT::SI_PREFIX, -0.00000000000000123],
            ['1.23f' , UNIT::SI_PREFIX, "+0.00000000000000123"],
            ['1.23f' , UNIT::SI_PREFIX, "0.00000000000000123"],
            ['1.23f' , UNIT::SI_PREFIX, 0.00000000000000123],
            ['1f'    , UNIT::SI_PREFIX, 0.000000000000001],
            ['1.23p' , UNIT::SI_PREFIX, 0.00000000000123],
            ['1p'    , UNIT::SI_PREFIX, 0.000000000001],
            ['1.23n' , UNIT::SI_PREFIX, 0.00000000123],
            ['1n'    , UNIT::SI_PREFIX, 0.000000001],
            ['1.23μ' , UNIT::SI_PREFIX, 0.00000123],
            ['1μ'    , UNIT::SI_PREFIX, 0.000001],
            ['1.23m' , UNIT::SI_PREFIX, 0.00123],
            ['1m'    , UNIT::SI_PREFIX, 0.001],
            ['12.3m' , UNIT::SI_PREFIX, 0.0123],
            ['10m'   , UNIT::SI_PREFIX, 0.01],
            ['123m'  , UNIT::SI_PREFIX, 0.123],
            ['100m'  , UNIT::SI_PREFIX, 0.1],
            ['1'     , UNIT::SI_PREFIX, 1],
            ['1.23'  , UNIT::SI_PREFIX, 1.23],
            ['10'    , UNIT::SI_PREFIX, 10],
            ['12.3'  , UNIT::SI_PREFIX, 12.3],
            ['100'   , UNIT::SI_PREFIX, 100],
            ['123'   , UNIT::SI_PREFIX, 123],
            ['1k'    , UNIT::SI_PREFIX, 1000],
            ['1.23k' , UNIT::SI_PREFIX, 1234],
            ['1M'    , UNIT::SI_PREFIX, 1000000],
            ['1.23M' , UNIT::SI_PREFIX, 1230000],
            ['1G'    , UNIT::SI_PREFIX, 1000000000],
            ['1.23G' , UNIT::SI_PREFIX, 1230000000],
            ['1T'    , UNIT::SI_PREFIX, 1000000000000],
            ['1.23T' , UNIT::SI_PREFIX, 1230000000000],
            ['1P'    , UNIT::SI_PREFIX, 1000000000000000],
            ['1.23P' , UNIT::SI_PREFIX, 1230000000000000],
            ['-1.23P', UNIT::SI_PREFIX, -1230000000000000],
            ['1E'    , UNIT::SI_PREFIX, 1000000000000000000],
            ['1.23E' , UNIT::SI_PREFIX, 1230000000000000000],
            ['1Z'    , UNIT::SI_PREFIX, 1000000000000000000000],
            ['1.23Z' , UNIT::SI_PREFIX, 1230000000000000000000],
            ['1,000Z', UNIT::SI_PREFIX, 1000000000000000000000000],
            ['1,000Z', UNIT::SI_PREFIX, 999999999999999983222784],
            ['1,000Z', UNIT::SI_PREFIX, '999999999999999983222784'],
            ['999.999999999999983222784Z', UNIT::SI_PREFIX, '999999999999999983222784', null, null],
            ['1Y'    , UNIT::SI_PREFIX, '1000000000000000000000000'],
            ['1Y'    , UNIT::SI_PREFIX, '1e24'],
            ['1.23Y' , UNIT::SI_PREFIX, 1230000000000000000000000],
            ['1,230Y', UNIT::SI_PREFIX, 1230000000000000000000000000],

            ['123.45k'              , UNIT::SI_PREFIX, '123.45k'            ],
            ['123,450'              , UNIT::SI_PREFIX, '123.45k'       , '' ],
            ['0.12M'                , UNIT::SI_PREFIX, '123.45k'       , 'M'],
            ['-0.12M'               , UNIT::SI_PREFIX, '-123.45k'      , 'M'],
            ['120k'                 , UNIT::SI_PREFIX, '0.12M'              ],
            ['1,234,567,890'        , UNIT::SI_PREFIX, '1,234,567,890' , '' ],
            ['1,234,567.89k'        , UNIT::SI_PREFIX, '1,234,567,890' , 'k'],
            ['1,234.57M'            , UNIT::SI_PREFIX, '1,234,567,890' , 'M'],
            ['1.23G'                , UNIT::SI_PREFIX, '1,234,567,890' , 'G'],
            ['1.23G'                , UNIT::SI_PREFIX, '1,234,567,890'      ],
            ['1,234,567,890,000,000', UNIT::SI_PREFIX, '1,234,567,890M', '' ],
            ['1,234,567,890,000k'   , UNIT::SI_PREFIX, '1,234,567,890M', 'k'],
            ['1,234,567,890M'       , UNIT::SI_PREFIX, '1,234,567,890M', 'M'],
            ['1,234,567.89G'        , UNIT::SI_PREFIX, '1,234,567,890M', 'G'],
            ['1,234.57T'            , UNIT::SI_PREFIX, '1,234,567,890M', 'T'],
            ['1.23P'                , UNIT::SI_PREFIX, '1,234,567,890M', 'P'],
            ['1.23P'                , UNIT::SI_PREFIX, '1,234,567,890M'     ],
            ['-1.23P'               , UNIT::SI_PREFIX, '-1,234,567,890M'    ],
            ['-1.23P'               , UNIT::SI_PREFIX, '-1234567890M'       ],

            ['1m', UNIT::SI_PREFIX, 0.00123, null, 0],
            ['1m', UNIT::SI_PREFIX, 0.001  , null, 0],
            ['1' , UNIT::SI_PREFIX, 1      , null, 0],
            ['1' , UNIT::SI_PREFIX, 1.23   , null, 0],
            ['1k', UNIT::SI_PREFIX, 1000   , null, 0],
            ['1k', UNIT::SI_PREFIX, 1234   , null, 0],
            ['2k', UNIT::SI_PREFIX, 1500   , null, 0],

            ['1'    , UNIT::SI_PREFIX, 1        , null, 2                        ],
            ['1.00' , UNIT::SI_PREFIX, 1        , null, 2, ['omit_zero' => false]],
            ['1.2μ' , UNIT::SI_PREFIX, 0.0000012, null, 2                        ],
            ['1.20μ', UNIT::SI_PREFIX, 0.0000012, null, 2, ['omit_zero' => false]],
            ['1k'   , UNIT::SI_PREFIX, 1000     , null, 2                        ],
            ['1.00k', UNIT::SI_PREFIX, 1000     , null, 2, ['omit_zero' => false]],
            ['1.2k' , UNIT::SI_PREFIX, 1200     , null, 2                        ],
            ['1.20k', UNIT::SI_PREFIX, 1200     , null, 2, ['omit_zero' => false]],
            ['1.21k', UNIT::SI_PREFIX, 1205     , null, 2                        ],

            ['1,023'   , UNIT::BINARY_PREFIX, '1023'      , null, 2],
            ['123.45Ki', UNIT::BINARY_PREFIX, '126412.8'  , null, 2],
            ['123.45Ki', UNIT::BINARY_PREFIX, '126,412.8' , null, 2],
            ['512Ki'   , UNIT::BINARY_PREFIX, '524288'    , null, 2],
            ['524,288' , UNIT::BINARY_PREFIX, '512Ki'     , ''  , 2],
            ['0.5Mi'   , UNIT::BINARY_PREFIX, '512Ki'     , 'Mi', 2],
            ['128Mi'   , UNIT::BINARY_PREFIX, '134217728' , null, 2],
            ['-128Mi'  , UNIT::BINARY_PREFIX, '-134217728', null, 2],

            ['1Y'  , UNIT::SI_PREFIX, '1,000,000,000,000,000,000,000,000'],
            ['1Z'  , UNIT::SI_PREFIX, '1,000,000,000,000,000,000,000'    ],
            ['1E'  , UNIT::SI_PREFIX, '1,000,000,000,000,000,000'        ],
            ['1P'  , UNIT::SI_PREFIX, '1,000,000,000,000,000'            ],
            ['1T'  , UNIT::SI_PREFIX, '1,000,000,000,000'                ],
            ['1G'  , UNIT::SI_PREFIX, '1,000,000,000'                    ],
            ['1M'  , UNIT::SI_PREFIX, '1,000,000'                        ],
            ['1k'  , UNIT::SI_PREFIX, '1,000'                            ],
            ['100' , UNIT::SI_PREFIX, '100'                              ],
            ['10'  , UNIT::SI_PREFIX, '10'                               ],
            ['1'   , UNIT::SI_PREFIX, '1'                                ],
            ['100m', UNIT::SI_PREFIX, '0.1'                              ],
            ['10m' , UNIT::SI_PREFIX, '0.01'                             ],
            ['1m'  , UNIT::SI_PREFIX, '0.001'                            ],
            ['1μ'  , UNIT::SI_PREFIX, '0.000001'                         ],
            ['1n'  , UNIT::SI_PREFIX, '0.000000001'                      ],
            ['1p'  , UNIT::SI_PREFIX, '0.000000000001'                   ],
            ['1f'  , UNIT::SI_PREFIX, '0.000000000000001'                ],
            ['1a'  , UNIT::SI_PREFIX, '0.000000000000000001'             ],
            ['1z'  , UNIT::SI_PREFIX, '0.000000000000000000001'          ],
            ['1y'  , UNIT::SI_PREFIX, '0.000000000000000000000001'       ],
            ['1h'  , UNIT::SI_PREFIX, '100'                              , 'h' ],
            ['1da' , UNIT::SI_PREFIX, '10'                               , 'da'],
            ['1d'  , UNIT::SI_PREFIX, '0.1'                              , 'd' ],
            ['1c'  , UNIT::SI_PREFIX, '0.01'                             , 'c' ],

            ['1,000,000,000,000,000,000,000,000', UNIT::SI_PREFIX, '1Y' , '', null],
            ['1,000,000,000,000,000,000,000'    , UNIT::SI_PREFIX, '1Z' , '', null],
            ['1,000,000,000,000,000,000'        , UNIT::SI_PREFIX, '1E' , '', null],
            ['1,000,000,000,000,000'            , UNIT::SI_PREFIX, '1P' , '', null],
            ['1,000,000,000,000'                , UNIT::SI_PREFIX, '1T' , '', null],
            ['1,000,000,000'                    , UNIT::SI_PREFIX, '1G' , '', null],
            ['1,000,000'                        , UNIT::SI_PREFIX, '1M' , '', null],
            ['1,000'                            , UNIT::SI_PREFIX, '1k' , '', null],
            ['100'                              , UNIT::SI_PREFIX, '1h' , '', null],
            ['10'                               , UNIT::SI_PREFIX, '1da', '', null],
            ['1'                                , UNIT::SI_PREFIX, '1'  , '', null],
            ['0.1'                              , UNIT::SI_PREFIX, '1d' , '', null],
            ['0.01'                             , UNIT::SI_PREFIX, '1c' , '', null],
            ['0.001'                            , UNIT::SI_PREFIX, '1m' , '', null],
            ['0.000001'                         , UNIT::SI_PREFIX, '1μ' , '', null],
            ['0.000000001'                      , UNIT::SI_PREFIX, '1n' , '', null],
            ['0.000000000001'                   , UNIT::SI_PREFIX, '1p' , '', null],
            ['0.000000000000001'                , UNIT::SI_PREFIX, '1f' , '', null],
            ['0.000000000000000001'             , UNIT::SI_PREFIX, '1a' , '', null],
            ['0.000000000000000000001'          , UNIT::SI_PREFIX, '1z' , '', null],
            ['0.000000000000000000000001'       , UNIT::SI_PREFIX, '1y' , '', null],

            ['1209600000[ms]', UNIT::TIME, '2[wk]' , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['172800000[ms]' , UNIT::TIME, '2[d]'  , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['7200000[ms]'   , UNIT::TIME, '2[h]'  , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['120000[ms]'    , UNIT::TIME, '2[min]', 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['2000[ms]'      , UNIT::TIME, '2[s]'  , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['2[ms]'         , UNIT::TIME, '2[ms]' , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['0.002[ms]'     , UNIT::TIME, '2[μs]' , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '']],
            ['7200000'       , UNIT::TIME, '2[h]'  , 'ms', null, ['before_prefix' => '[', 'after_prefix' => ']', 'thousands_separator' => '', 'without_prefix' => true]],

            ['2.54cm'     , UNIT::LENGTH, '1in', 'cm'],
            ['1,609.34m'   , UNIT::LENGTH, '1mi', 'm'],

            ['1.23t'        , UNIT::MASS, '1,234kg'            ],
            ['0.45359237kg' , UNIT::MASS, '1lb'    , 'kg', null],

            ['12.35kA', UNIT::ELECTRIC_CURRENT, '12345'],
            ['2,997,924,536.84esu/s', UNIT::ELECTRIC_CURRENT, '1A', 'esu/s'],

            ['0°C'     , UNIT::TEMPERATURE, '273.15K', '°C' ],
            ['273.15K' , UNIT::TEMPERATURE, '0°C'    , 'K'  ],
            ['726.85°C', UNIT::TEMPERATURE, '1kK'    , '°C' ],
            ['0°F'     , UNIT::TEMPERATURE, '255.37K', '°F' ],
            ['255.37K' , UNIT::TEMPERATURE, '0°F'    , 'K'  ],
            ['50°F'    , UNIT::TEMPERATURE, '10°C'   , '°F' ],
        ];
    }

    /**
     * @dataProvider dataExchanges
     */
    public function test_exchange($expect, $units, $value, ?string $to = null, ?int $precision = 2, array $options = [])
    {
        $this->assertSame($expect, Unit::of($units)->exchange($value, $to, $precision, $options));
    }

    public function test_exchange_reversible()
    {
        foreach (Unit::config('factors') as $unit_name => $flactors) {
            $base_unit = Unit::baseUnitOf($flactors);
            if ($base_unit === null) {
                continue;
            }
            foreach ($flactors as $symbole => [$flactor, $auto_scalable]) {
                $unit = Unit::of($unit_name, ['thousands_separator' => '']);
                foreach (['1', '0.123', '9876.543', '-12.34'] as $v) {
                    $origin     = "{$v}{$symbole}";
                    $exchangeed = $unit->exchange($origin, $base_unit, null);
                    $reversed   = $unit->exchange($exchangeed, $symbole, 3);
                    $flactor    = is_array($flactor) ? '*Convert function*' : $flactor ;
                    $this->assertSame($origin, $reversed, "Faled in {$unit_name} {$symbole} => {$flactor} is not reversible, {$origin} -> {$exchangeed} -> {$reversed}");
                }
            }
        }
    }

    public function dataConverts() : array
    {
        return [
            ['0.000000000000000000000000123', Unit::SI_PREFIX, '0.123y', ''],
            ['0.000000000000000000000000123', Unit::SI_PREFIX, '0.123y'],

            ['1.234'      , UNIT::MASS, '1,234kg', 't' ],
            ['0.45359237' , UNIT::MASS, '1lb'    , 'kg'],
            ['0.45359237' , UNIT::MASS, '1lb'          ],

            ['12345', UNIT::ELECTRIC_CURRENT, '12.345kA', 'A'],
            ['2997924536.843143491760654099167146584419606306553972684710375007382389171976240848460610719199098464', UNIT::ELECTRIC_CURRENT, '1A', 'esu/s'],

            ['273.15', Unit::TEMPERATURE, '0°C', 'K'],

        ];
    }

    /**
     * @dataProvider dataConverts
     */
    public function test_convert($expect, $units, $value, ?string $to = null, array $options = [])
    {
        $decimal = Unit::of($units)->convert($value, $to, $options);
        $this->assertInstanceOf(Decimal::class, $decimal);
        $this->assertSame($expect, $decimal->value());
    }
}
