<?php
namespace Rebet\Tests\Tools;

use Rebet\Tools\Decimal;
use Rebet\Tests\RebetTestCase;

class DecimalTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        Decimal::setMaxScale(30);
    }

    public function test_setMode()
    {
        $this->assertSame(Decimal::MODE_AUTO_PRECISION_SCALING, Decimal::config('mode'));
        Decimal::setMode(Decimal::MODE_FIXED_DECIMAL_PLACES);
        $this->assertSame(Decimal::MODE_FIXED_DECIMAL_PLACES, Decimal::config('mode'));
    }

    public function test_setFixedScale()
    {
        $this->assertSame(2, Decimal::config('options.fixed_scale'));
        Decimal::setFixedScale(3);
        $this->assertSame(3, Decimal::config('options.fixed_scale'));
    }

    public function test_setGuardDigits()
    {
        $this->assertSame(4, Decimal::config('options.guard_digits'));
        Decimal::setGuardDigits(2);
        $this->assertSame(2, Decimal::config('options.guard_digits'));
    }

    public function test_setMaxScale()
    {
        $this->assertSame(30, Decimal::config('options.max_scale'));
        Decimal::setMaxScale(10);
        $this->assertSame(10, Decimal::config('options.max_scale'));
    }

    public function test_value()
    {
        Decimal::setMode(Decimal::MODE_SIGNIFICANCE_ARITHMETIC);

        $this->assertSame('1234', Decimal::of('1234')->value());
        $this->assertSame('1234', Decimal::of('1.234e3')->value());

        $result = Decimal::of('1')->div('3');
        $this->assertSame('0.33333', $result->value());
        $this->assertSame('0.3', $result->value(false));

        $result = Decimal::of('1.0')->div('3.0');
        $this->assertSame('0.333333', $result->value());
        $this->assertSame('0.33', $result->value(false));

        $result = $result->add('1.1');
        $this->assertSame('1.43333', $result->value());
        $this->assertSame('1.4', $result->value(false));

        $result = $result->div('0.3');
        $this->assertSame('4.7778', $result->value());
        $this->assertSame('5', $result->value(false));
    }

    public function test_scale()
    {
        Decimal::setMode(Decimal::MODE_SIGNIFICANCE_ARITHMETIC);

        $this->assertSame(0, Decimal::of('1234')->scale());
        $this->assertSame(0, Decimal::of('1.234e3')->scale());
        $this->assertSame(3, Decimal::of('1.234')->scale());
        $this->assertSame(3, Decimal::of('1e-3')->scale());

        $result = Decimal::of('1')->div('3');
        $this->assertSame(5, $result->scale());
        $this->assertSame(1, $result->scale(false));

        $result = Decimal::of('1.0')->div('3.0');
        $this->assertSame(6, $result->scale());
        $this->assertSame(2, $result->scale(false));

        $result = $result->add('1.1');
        $this->assertSame(5, $result->scale());
        $this->assertSame(1, $result->scale(false));

        $result = $result->div('0.3');
        $this->assertSame(4, $result->scale());
        $this->assertSame(0, $result->scale(false));
    }

    public function test_significantFigures()
    {
        Decimal::setMode(Decimal::MODE_SIGNIFICANCE_ARITHMETIC);

        $this->assertSame(4, Decimal::of('1234')->significantFigures());
        $this->assertSame(4, Decimal::of('1.0e3')->significantFigures());
        $this->assertSame(2, Decimal::of('1.0e3')->significantFigures(false));
        $this->assertSame(4, Decimal::of('1.234')->significantFigures());
        $this->assertSame(2, Decimal::of('0.012')->significantFigures());
        $this->assertSame(1, Decimal::of('1e-3')->significantFigures());

        $result = Decimal::of('1')->div('3');
        $this->assertSame(5, $result->significantFigures());
        $this->assertSame(1, $result->significantFigures(false));

        $result = Decimal::of('1.0')->div('3.0');
        $this->assertSame(6, $result->significantFigures());
        $this->assertSame(2, $result->significantFigures(false));

        $result = $result->add('1.1');
        $this->assertSame(6, $result->significantFigures());
        $this->assertSame(2, $result->significantFigures(false));

        $result = $result->div('0.3');
        $this->assertSame(5, $result->significantFigures());
        $this->assertSame(1, $result->significantFigures(false));
    }

    public function test_guardDigits()
    {
        Decimal::setMode(Decimal::MODE_SIGNIFICANCE_ARITHMETIC);

        $this->assertSame(0, Decimal::of('1234')->guardDigits());
        $this->assertSame(2, Decimal::of('1.0e3')->guardDigits());
        $this->assertSame(3, Decimal::of('1e3')->guardDigits());
        $this->assertSame(0, Decimal::of('1.234')->guardDigits());
        $this->assertSame(0, Decimal::of('0.012')->guardDigits());
        $this->assertSame(0, Decimal::of('1e-3')->guardDigits());

        $result = Decimal::of('1')->div('3');
        $this->assertSame(4, $result->guardDigits());

        $result = Decimal::of('1.0')->div('3.0');
        $this->assertSame(4, $result->guardDigits());

        $result = $result->add('1.1');
        $this->assertSame(4, $result->guardDigits());

        $result = $result->div('0.3');
        $this->assertSame(4, $result->guardDigits());
    }

    public function test_isDirty()
    {
        $string = Decimal::of('1.1');
        $int    = Decimal::of(1);
        $float  = Decimal::of(1.1);
        $this->assertFalse($string->isDirty());
        $this->assertFalse($int->isDirty());
        $this->assertTrue($float->isDirty());

        foreach (['shift', 'unshift', 'floor', 'ceil'] as $formula) {
            $this->assertFalse($string->$formula(1)->isDirty());
            $this->assertFalse($int->$formula(1)->isDirty());
            $this->assertTrue($float->$formula(1)->isDirty());
        }

        foreach (['abs', 'compact', 'sqrt'] as $formula) {
            $this->assertFalse($string->$formula()->isDirty());
            $this->assertFalse($int->$formula()->isDirty());
            $this->assertTrue($float->$formula()->isDirty());
        }

        foreach (['add', 'sub', 'mul', 'div', 'pow', 'mod'] as $formula) {
            $this->assertFalse($string->$formula($string)->isDirty());
            $this->assertFalse($string->$formula($int)->isDirty());
            $this->assertTrue($string->$formula($float)->isDirty());
            $this->assertFalse($string->$formula('1.1')->isDirty());
            $this->assertFalse($string->$formula(1)->isDirty());
            $this->assertTrue($string->$formula(1.1)->isDirty());
            $this->assertTrue($float->$formula($string)->isDirty());
            $this->assertTrue($float->$formula($int)->isDirty());
            $this->assertTrue($float->$formula($float)->isDirty());
            $this->assertTrue($float->$formula('1.1')->isDirty());
            $this->assertTrue($float->$formula(1)->isDirty());
            $this->assertTrue($float->$formula(1.1)->isDirty());
        }

        $this->assertFalse($string->powmod($string, $int)->isDirty());
        $this->assertTrue($string->powmod($string, $float)->isDirty());
        $this->assertTrue($string->powmod($float, $string)->isDirty());
        $this->assertTrue($float->powmod($int, $string)->isDirty());
    }

    public function test___toString()
    {
        Decimal::setMode(Decimal::MODE_SIGNIFICANCE_ARITHMETIC);

        $this->assertSame('1234', Decimal::of('1234')->__toString());
        $this->assertSame('1234.56', Decimal::of('1234.56')->__toString());
        $this->assertSame('0.0012', Decimal::of('0.0012')->__toString());
        $this->assertSame('1000 (1 sf)', Decimal::of('1e3')->__toString());
        $this->assertSame('1200 (2 sf)', Decimal::of('1.2e3')->__toString());

        $result = Decimal::of('1.0')->div('3.0');
        $this->assertSame('0.333333 (2 sf)', $result->__toString());
        $result = $result->add('1.1');
        $this->assertSame('1.43333 (2 sf)', $result->__toString());
        $result = $result->div('0.3');
        $this->assertSame('4.7778 (1 sf)', $result->__toString());
    }

    public function test_compact()
    {
        $decimal = Decimal::of('0.01000');
        $this->assertSame('0.01000', $decimal->value());
        $this->assertSame('0.01', $decimal->compact()->value());
    }

    public function test_normalize()
    {
        Decimal::setMode(Decimal::MODE_SIGNIFICANCE_ARITHMETIC);

        $this->assertSame('1234', Decimal::of('1234')->normalize()->__toString());
        $this->assertSame('1234.56', Decimal::of('1234.56')->normalize()->__toString());
        $this->assertSame('0.0012', Decimal::of('0.0012')->normalize()->__toString());
        $this->assertSame('1000 (1 sf)', Decimal::of('1e3')->normalize()->__toString());
        $this->assertSame('1200 (2 sf)', Decimal::of('1.2e3')->normalize()->__toString());

        $result = Decimal::of('1.0')->div('3.0');
        $this->assertSame('0.333333 (2 sf)', $result->__toString());
        $this->assertSame('0.33', $result->normalize()->__toString());
        $result = $result->add('1.1');
        $this->assertSame('1.43333 (2 sf)', $result->__toString());
        $this->assertSame('1.4', $result->normalize()->__toString());
        $result = $result->div('0.3');
        $this->assertSame('4.7778 (1 sf)', $result->__toString());
        $this->assertSame('5', $result->normalize()->__toString());
    }

    public function test_scaleOf()
    {
        $delegator = new class('1') extends Decimal {
            public function invoke($value)
            {
                return static::scaleOf($value);
            }
        };
        $this->assertSame(0, $delegator->invoke(''));
        $this->assertSame(0, $delegator->invoke('0'));
        $this->assertSame(0, $delegator->invoke('012'));
        $this->assertSame(0, $delegator->invoke('0.'));
        $this->assertSame(1, $delegator->invoke('0.0'));
        $this->assertSame(3, $delegator->invoke('0.000'));
        $this->assertSame(3, $delegator->invoke('-0.000'));
        $this->assertSame(3, $delegator->invoke('.000'));
        $this->assertSame(0, $delegator->invoke('1000'));
    }

    public function test_significantFiguresOf()
    {
        $delegator = new class('1') extends Decimal {
            public function invoke($value)
            {
                return static::significantFiguresOf($value);
            }
        };
        $this->assertSame(1, $delegator->invoke(''));
        $this->assertSame(1, $delegator->invoke('0'));
        $this->assertSame(2, $delegator->invoke('012'));
        $this->assertSame(1, $delegator->invoke('0.'));
        $this->assertSame(5, $delegator->invoke('0.0000'));
        $this->assertSame(3, $delegator->invoke('000.00'));
        $this->assertSame(2, $delegator->invoke('1.0'));
        $this->assertSame(3, $delegator->invoke('1.01'));
        $this->assertSame(1, $delegator->invoke('0.01'));
        $this->assertSame(1, $delegator->invoke('0.0001'));
        $this->assertSame(1, $delegator->invoke('.0001'));
        $this->assertSame(4, $delegator->invoke('-1.000'));
        $this->assertSame(2, $delegator->invoke('-0.0012'));
        $this->assertSame(4, $delegator->invoke('1000'));
    }

    public function dataConstructs() : array
    {
        return [
            ['0'                         , 1  , '0'                        ],
            ['3'                         , 1  , '03'                       ],
            ['0.3'                       , 1  , '00.3'                     ],
            ['123'                       , 3  , '123'                      ],
            ['123.45'                    , 5  , '123.45'                   ],
            ['123.45'                    , 5  , '+123.45'                  ],
            ['-123.45'                   , 5  , '-123.45'                  ],
            ['123.450'                   , 6  , '123.450'                  ],
            ['1000'                      , 1  , '1e3'                      ],
            ['1000'                      , 1  , '1E3'                      ],
            ['1000'                      , 1  , '1E+3'                     ],
            ['1000'                      , 2  , '1.0e3'                    ],
            ['1000.0'                    , 5  , '1.0000e3'                 ],
            ['1230'                      , 3  , '1.23e3'                   ],
            ['1234.5'                    , 5  , '1.2345e3'                 ],
            ['-1000'                     , 1  , '-1e3'                     ],
            ['-1230'                     , 3  , '-1.23e3'                  ],
            ['-1234.5'                   , 5  , '-1.2345e3'                ],
            ['0.001'                     , 1  , '1e-3'                     ],
            ['0.001'                     , 1  , '1E-3'                     ],
            ['0.0010'                    , 2  , '1.0e-3'                   ],
            ['-0.001'                    , 1  , '-1e-3'                    ],
            ['0.001'                     , 1  , '+1e-3'                    ],
            ['0.00123'                   , 3  , '1.23e-3'                  ],
            ['1.000'                     , 4  , '1000e-3'                  ],
            ['1.230'                     , 4  , '1230e-3'                  ],
            ['12.30'                     , 4  , '1230e-2'                  ],
            ['1000000000000000000000000' , 1  , '1e24'                     ], // yotta
            ['0.000000000000000000000001', 1  , '1e-24'                    ], // yocto
            ['1208925819614629174706176' , 25 , bcpow('2', '80')           ], // yobi
            ['12345'                     , 5  , '12,345'                   ],
            ['1234567.89'                , 9  , '1,234,567.89'             ],
            ['1234567.89'                , 9  , '1 234 567,89'  , ',' , ' '],
            ['12.300'                    , 5  , '1,230.0e-2'               ],
            ['1234'                      , 2  , '1,234 (2 sf)'             ],
            ['1.23456'                   , 3  , '1.23456 (3 sf)'           ],
        ];
    }

    /**
     * @dataProvider dataConstructs
     */
    public function test___construct($expect, int $expect_sf, $value, string $decimal_point = ".", string $thousands_separator = ",")
    {
        $decimal = new Decimal($value, $decimal_point, $thousands_separator);
        $this->assertInstanceOf(Decimal::class, $decimal);
        $this->assertSame($expect, $decimal->value());
        $this->assertSame($expect_sf, $decimal->significantFigures(false));
    }

    public function dataOfs() : array
    {
        return [
            ['0'                       , 0              ],
            ['123'                     , 123            ],
            ['-123'                    , -123           ],
            ['1.2'                     , 1.2            ],
            ['999999999999999983222784', 1e24           ], // float to string convert roundoff error.
            ['9223372036854775807'     , PHP_INT_MAX    ],
            ['9223372036854775808'     , PHP_INT_MAX + 1],
            ['-9223372036854775808'    , PHP_INT_MIN    ],
            ['-9223372036854775808'    , PHP_INT_MIN - 1], // float to string convert roundoff error.
        ];
    }

    /**
     * @dataProvider dataOfs
     */
    public function test_of($expect, $value, string $decimal_point = ".", string $thousands_separator = ",")
    {
        $decimal = Decimal::of($value, $decimal_point, $thousands_separator);
        $this->assertInstanceOf(Decimal::class, $decimal);
        $this->assertSame($expect, $decimal->value());
        $this->assertSame(is_float($value), $decimal->isDirty());
    }

    public function test_of_null()
    {
        $this->assertNull(Decimal::of(null));
    }

    public function test_abs()
    {
        $this->assertSame('123', Decimal::of('123')->abs()->value());
        $this->assertSame('123', Decimal::of('-123')->abs()->value());
        $this->assertSame('123.24', Decimal::of('-123.24')->abs()->value());
        $this->assertSame('1000', Decimal::of('-1e3')->abs()->value());
        $this->assertSame('0.001', Decimal::of('-1e-3')->abs()->value());
    }

    public function test_comp()
    {
        $this->assertSame(0, Decimal::of('1')->comp('1'));
        $this->assertSame(0, Decimal::of('1')->comp('1.0'));
        $this->assertSame(0, Decimal::of('1')->comp('1.01', 1));
        $this->assertSame(-1, Decimal::of('1')->comp('1.01'));
        $this->assertSame(1, Decimal::of('1')->comp('0.99'));
        $this->assertSame(-1, Decimal::of('1')->comp('1.00000000000000001'));
        $this->assertSame(1, Decimal::of('0.00001')->comp('0'));
        $this->assertSame(0, Decimal::of('0.00001')->comp('0', 3));
    }

    public function test_eq()
    {
        $this->assertSame(false, Decimal::of('1')->eq('0.9'));
        $this->assertSame(true, Decimal::of('1')->eq('1.0'));
        $this->assertSame(false, Decimal::of('1')->eq('1.1'));

        $this->assertSame(false, Decimal::of('1')->eq('0.9', 0));
        $this->assertSame(true, Decimal::of('1')->eq('1.0', 0));
        $this->assertSame(true, Decimal::of('1')->eq('1.1', 0));
    }

    public function test_lt()
    {
        $this->assertSame(false, Decimal::of('1')->lt('0.9'));
        $this->assertSame(false, Decimal::of('1')->lt('1.0'));
        $this->assertSame(true, Decimal::of('1')->lt('1.1'));

        $this->assertSame(false, Decimal::of('1')->lt('0.9', 0));
        $this->assertSame(false, Decimal::of('1')->lt('1.0', 0));
        $this->assertSame(false, Decimal::of('1')->lt('1.1', 0));
    }

    public function test_lte()
    {
        $this->assertSame(false, Decimal::of('1')->lte('0.9'));
        $this->assertSame(true, Decimal::of('1')->lte('1.0'));
        $this->assertSame(true, Decimal::of('1')->lte('1.1'));

        $this->assertSame(false, Decimal::of('1')->lte('0.9', 0));
        $this->assertSame(true, Decimal::of('1')->lte('1.0', 0));
        $this->assertSame(true, Decimal::of('1')->lte('1.1', 0));
    }

    public function test_gte()
    {
        $this->assertSame(true, Decimal::of('1')->gte('0.9'));
        $this->assertSame(true, Decimal::of('1')->gte('1.0'));
        $this->assertSame(false, Decimal::of('1')->gte('1.1'));

        $this->assertSame(true, Decimal::of('1')->gte('0.9', 0));
        $this->assertSame(true, Decimal::of('1')->gte('1.0', 0));
        $this->assertSame(true, Decimal::of('1')->gte('1.1', 0));
    }

    public function test_gt()
    {
        $this->assertSame(true, Decimal::of('1')->gt('0.9'));
        $this->assertSame(false, Decimal::of('1')->gt('1.0'));
        $this->assertSame(false, Decimal::of('1')->gt('1.1'));

        $this->assertSame(true, Decimal::of('1')->gt('0.9', 0));
        $this->assertSame(false, Decimal::of('1')->gt('1.0', 0));
        $this->assertSame(false, Decimal::of('1')->gt('1.1', 0));
    }

    public function test_isNegative()
    {
        $this->assertTrue(Decimal::of('-123.1')->isNegative());
        $this->assertTrue(Decimal::of('-1')->isNegative());
        $this->assertTrue(Decimal::of('-0.1')->isNegative());
        $this->assertTrue(Decimal::of('-0.00000000000001')->isNegative());
        $this->assertFalse(Decimal::of('-0.0')->isNegative());
        $this->assertFalse(Decimal::of('-0')->isNegative());
        $this->assertFalse(Decimal::of('0')->isNegative());
        $this->assertFalse(Decimal::of('0.1')->isNegative());
        $this->assertFalse(Decimal::of('1')->isNegative());
        $this->assertFalse(Decimal::of('123.1')->isNegative());
    }

    public function test_shift()
    {
        $this->assertSame('0.012345', Decimal::of('123.45')->shift(-4)->value());
        $this->assertSame('0.12345', Decimal::of('123.45')->shift(-3)->value());
        $this->assertSame('1.2345', Decimal::of('123.45')->shift(-2)->value());
        $this->assertSame('12.345', Decimal::of('123.45')->shift(-1)->value());
        $this->assertSame('123.45', Decimal::of('123.45')->shift(0)->value());
        $this->assertSame('1234.5', Decimal::of('123.45')->shift(1)->value());
        $this->assertSame('12345', Decimal::of('123.45')->shift(2)->value());
        $this->assertSame('123450', Decimal::of('123.45')->shift(3)->value());
        $this->assertSame('1234500', Decimal::of('123.45')->shift(4)->value());
    }

    public function test_unshift()
    {
        $this->assertSame('1234.5', Decimal::of('123.45')->unshift(-1)->value());
        $this->assertSame('123.45', Decimal::of('123.45')->unshift(0)->value());
        $this->assertSame('12.345', Decimal::of('123.45')->unshift(1)->value());
    }

    public function test_integers()
    {
        $this->assertSame('123', Decimal::of('123.45')->integers());
        $this->assertSame('-123', Decimal::of('-123.45')->integers());
    }

    public function test_decimals()
    {
        $this->assertSame('0', Decimal::of('123')->decimals());
        $this->assertSame('00', Decimal::of('123.00')->decimals());
        $this->assertSame('45', Decimal::of('123.45')->decimals());
        $this->assertSame('45', Decimal::of('-123.45')->decimals());
    }

    public function dataFloors() : array
    {
        return [
            ['123', '123'],
            ['123', '123.444444444'],
            ['123', '123.555555555'],
            ['123', '123.000000001'],
            ['-123', '-123'],

            ['123.0', '123', 1],
            ['123.4', '123.444444444', 1],
            ['123.5', '123.555555555', 1],
            ['123.0', '123.000000001', 1],
            ['-123.5', '-123.444444444', 1],

            ['123.00', '123', 2],
            ['123.44', '123.444444444', 2],
            ['123.55', '123.555555555', 2],
            ['123.00', '123.000000001', 2],
            ['-123.45', '-123.444444444', 2],

            ['120', '123', -1],
            ['120', '123.444444444', -1],
            ['120', '123.555555555', -1],
            ['120', '123.000000001', -1],
            ['-130', '-123.444444444', -1],

            ['100', '123', -2],
            ['0', '123', -3],
            ['0', '123', -4],
            ['-200', '-123', -2],
            ['-1000', '-123', -3],
            ['-10000', '-123', -4],
        ];
    }

    /**
     * @dataProvider dataFloors
     */
    public function test_floor($expect, $value, $precision = 0)
    {
        $this->assertSame($expect, Decimal::of($value)->floor($precision)->value());
    }

    public function dataCeils() : array
    {
        return [
            ['123', '123'],
            ['124', '123.444444444'],
            ['124', '123.555555555'],
            ['124', '123.000000001'],
            ['-123', '-123'],
            ['-123', '-123.1'],

            ['123.0', '123', 1],
            ['123.5', '123.444444444', 1],
            ['123.6', '123.555555555', 1],
            ['123.1', '123.000000001', 1],
            ['-123.4', '-123.444444444', 1],

            ['123.00', '123', 2],
            ['123.45', '123.444444444', 2],
            ['123.56', '123.555555555', 2],
            ['123.01', '123.000000001', 2],
            ['-123.44', '-123.444444444', 2],

            ['130', '123', -1],
            ['130', '123.444444444', -1],
            ['130', '123.555555555', -1],
            ['130', '123.000000001', -1],
            ['-120', '-123.444444444', -1],

            ['200', '123', -2],
            ['1000', '123', -3],
            ['10000', '123', -4],
            ['-100', '-123', -2],
            ['0', '-123', -3],
            ['0', '-123', -4],
        ];
    }

    /**
     * @dataProvider dataCeils
     */
    public function test_ceil($expect, $value, $precision = 0)
    {
        $this->assertSame($expect, Decimal::of($value)->ceil($precision)->value());
    }

    public function dataRounds() : array
    {
        return [
            ['123', '123'],
            ['123', '123.444444444'],
            ['124', '123.555555555'],
            ['123', '123.000000001'],
            ['-123', '-123.444444444'],
            ['-124', '-123.555555555'],

            ['123.0', '123', 1],
            ['123.4', '123.444444444', 1],
            ['123.6', '123.555555555', 1],
            ['123.0', '123.000000001', 1],
            ['-123.4', '-123.444444444', 1],
            ['-123.6', '-123.555555555', 1],

            ['123.00', '123', 2],
            ['123.44', '123.444444444', 2],
            ['123.56', '123.555555555', 2],
            ['123.00', '123.000000001', 2],
            ['-123.44', '-123.444444444', 2],
            ['-123.56', '-123.555555555', 2],

            ['120', '123', -1],
            ['120', '123.444444444', -1],
            ['120', '123.555555555', -1],
            ['120', '123.000000001', -1],
            ['460', '456', -1],
            ['-120', '-123', -1],
            ['-460', '-456', -1],

            ['100', '123', -2],
            ['500', '456', -2],
            ['600', '567', -2],
            ['0', '123', -3],
            ['0', '456', -3],
            ['1000', '567', -3],
            ['0', '567', -4],
            ['-100', '-123', -2],
            ['-500', '-456', -2],
            ['-600', '-567', -2],
            ['0', '-123', -3],
            ['0', '-456', -3],
            ['-1000', '-567', -3],
            ['0', '-567', -4],

            ['3456.0', '3456', 5, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['3456', '3456', 4, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['3460', '3456', 3, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['3500', '3456', 2, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['3000', '3456', 1, Decimal::TYPE_SIGNIFICANT_FIGURES],

            ['0.034560', '0.03456', 5, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['0.03456', '0.03456', 4, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['0.0346', '0.03456', 3, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['0.035', '0.03456', 2, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['0.03', '0.03456', 1, Decimal::TYPE_SIGNIFICANT_FIGURES],

            ['1.0346', '1.03456', 5, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['1.035', '1.03456', 4, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['1.03', '1.03456', 3, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['1.0', '1.03456', 2, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['1', '1.03456', 1, Decimal::TYPE_SIGNIFICANT_FIGURES],

            ['123.0346', '123.03456', 7, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['123.035', '123.03456', 6, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['123.03', '123.03456', 5, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['123.0', '123.03456', 4, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['123', '123.03456', 3, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['120', '123.03456', 2, Decimal::TYPE_SIGNIFICANT_FIGURES],
            ['100', '123.03456', 1, Decimal::TYPE_SIGNIFICANT_FIGURES],
        ];
    }

    /**
     * @dataProvider dataRounds
     */
    public function test_round($expect, $value, $precision = 0, $type = Decimal::TYPE_DECIMAL_PLACES)
    {
        $this->assertSame($expect, Decimal::of($value)->round($precision, 0, $type)->value());
    }

    public function dataAdds() : array
    {
        return [
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2.53', '2', '0.53'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3', '1', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3.0', '1.0', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3.32', '1.1', '2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '123.456', '123', '0.456'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.12', '-1.1', '2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '-1.12', '1.1', '-2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3.00', '1', '2', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '123.46', '123', '0.456', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1000000000000000000000001.00', '1e24', '1', 2],

            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.5300', '2', '0.53'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.0000', '1', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.0000', '1.0', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.32000', '1.1', '2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '123.4560', '123', '0.456'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.12000', '-1.1', '2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '-1.12000', '1.1', '-2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.00', '1', '2', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '123.46', '123', '0.456', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1000000000000000000000001.00', '1e24', '1', 2],

            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.530000', '2', '0.53'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.000000', '1', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.000000', '1.0', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.320000', '1.1', '2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '123.456000', '123', '0.456'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.120000', '-1.1', '2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '-1.120000', '1.1', '-2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.00', '1', '2', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '123.46', '123', '0.456', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1000000000000000000000001.00', '1e24', '1', 2],
        ];
    }

    /**
     * @dataProvider dataAdds
     */
    public function test_add($mode, $expect, $left, $right, $scale = null)
    {
        Decimal::setMode($mode);
        $this->assertSame($expect, Decimal::of($left)->add($right, $scale)->value());
    }

    public function dataSubs() : array
    {
        return [
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.47', '2', '0.53'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '-1', '1', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '-1.0', '1.0', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '-1.12', '1.1', '2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '122.544', '123', '0.456'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.47', '2', '0.53'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '-3.32', '-1.1', '2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3.32', '1.1', '-2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '-1.00', '1', '2', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '122.54', '123', '0.456', 2],

            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.4700', '2', '0.53'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '-1.0000', '1', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '-1.0000', '1.0', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '-1.12000', '1.1', '2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '122.5440', '123', '0.456'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.4700', '2', '0.53'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '-3.32000', '-1.1', '2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.32000', '1.1', '-2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '-1.00', '1', '2', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '122.54', '123', '0.456', 2],

            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.470000', '2', '0.53'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '-1.000000', '1', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '-1.000000', '1.0', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '-1.120000', '1.1', '2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '122.544000', '123', '0.456'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.470000', '2', '0.53'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '-3.320000', '-1.1', '2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.320000', '1.1', '-2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '-1.00', '1', '2', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '122.54', '123', '0.456', 2],
        ];
    }

    /**
     * @dataProvider dataSubs
     */
    public function test_sub($mode, $expect, $left, $right, $scale = null)
    {
        Decimal::setMode($mode);
        $this->assertSame($expect, Decimal::of($left)->sub($right, $scale)->value());
    }

    public function dataMuls() : array
    {
        return [
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.06', '2', '0.53'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.0', '1.1', '0'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2', '1', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2.0', '1.0', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2.442', '1.1', '2.22'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '15129', '123', '123'],  // 15129
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.06', '2', '0.53'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.000506', '2.53', '0.0002'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.000000075', '0.00025', '0.0003'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.00', '1.1', '0', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2.00', '1', '2', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2.00', '1.0', '2', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '2.44', '1.1', '2.22', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.06', '2', '0.53', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.00', '2.53', '0.0002', 2],

            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.0600', '2', '0.53'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.0000', '1.1', '0'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.0000', '1', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.00000', '1.0', '2.0'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.44200', '1.1', '2.22'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.00050600', '2.53', '0.0002'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.000000075000', '0.00025', '0.0003'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.00', '1.1', '0', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.00', '1', '2', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.00', '1.0', '2', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '2.44', '1.1', '2.22', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.06', '2', '0.53', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.00', '2.53', '0.0002', 2],

            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.060000', '2', '0.53'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.000000', '1.1', '0'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.000000', '1', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.000000', '1.0', '2.0'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.442000', '1.1', '2.22'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.000506', '2.53', '0.0002'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.000000', '0.00025', '0.0003'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.00', '1.1', '0', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.00', '1', '2', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.00', '1.0', '2', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '2.44', '1.1', '2.22', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.06', '2', '0.53', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.00', '2.53', '0.0002', 2],
        ];
    }

    /**
     * @dataProvider dataMuls
     */
    public function test_mul($mode, $expect, $left, $right, $scale = null)
    {
        Decimal::setMode($mode);
        $this->assertSame($expect, Decimal::of($left)->mul($right, $scale)->value());
    }

    public function dataDivs() : array
    {
        return [
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.016', '2', '125'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.666666666666666666666666666667', '2', '3'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '666.666666666666666666666666666667', '2', '0.003'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '500', '2', '0.004'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.000000000000000000000000002', '2', '1e27'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0', '2', '1e33'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.5', '1', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.5', '1.0', '2.0'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.495495495495495495495495495495', '1.1', '2.22'], // 0.49549549549
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3.773584905660377358490566037736', '2', '0.53'], // 3.77358490566
            [Decimal::MODE_AUTO_PRECISION_SCALING, '12650', '2.53', '0.0002'], // 12650
            [Decimal::MODE_AUTO_PRECISION_SCALING, '84333.333333333333333333333333333333', '2.53', '0.00003'], // 84333.3333333
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.234', '1234', '1000'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.234', '1234', '1.0e3'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.50', '1', '2', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.50', '1.0', '2', 2],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.50', '1.1', '2.22', 2], // 0.49549549549
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3.77', '2', '0.53', 2], // 3.77358490566
            [Decimal::MODE_AUTO_PRECISION_SCALING, '12650.00', '2.53', '0.0002', 2], // 12650
            [Decimal::MODE_AUTO_PRECISION_SCALING, '84333.33', '2.53', '0.00003', 2], // 84333.3333333

            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.016000', '2', '125'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.66667', '2', '3'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '666.67', '2', '0.003'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '500.00', '2', '0.004'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.0000000000000000000000000020000', '2', '1e27'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.0000000000000000000000000000000020000', '2', '1e33'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.50000', '1', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.500000', '1.0', '2.0'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.495495', '1.1', '2.22'], // 0.49549549549
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.7736', '2', '0.53'], // 3.77358490566
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '12650', '2.53', '0.0002'], // 12650
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '84333', '2.53', '0.00003'], // 84333.3333333
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.2340000', '1234', '1000'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.23400', '1234', '1.0e3'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.50', '1', '2', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.50', '1.0', '2', 2],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.50', '1.1', '2.22', 2], // 0.49549549549
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.77', '2', '0.53', 2], // 3.77358490566
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '12650.00', '2.53', '0.0002', 2], // 12650
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '84333.33', '2.53', '0.00003', 2], // 84333.3333333

            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.016000', '2', '125'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.666667', '2', '3'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '666.666667', '2', '0.003'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '500.000000', '2', '0.004'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.000000', '2', '1e27'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.000000', '2', '1e33'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.500000', '1', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.500000', '1.0', '2.0'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.495495', '1.1', '2.22'], // 0.49549549549
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.773585', '2', '0.53'], // 3.77358490566
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '12650.000000', '2.53', '0.0002'], // 12650
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '84333.333333', '2.53', '0.00003'], // 84333.3333333
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.50', '1', '2', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.50', '1.0', '2', 2],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.50', '1.1', '2.22', 2], // 0.49549549549
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.77', '2', '0.53', 2], // 3.77358490566
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '12650.00', '2.53', '0.0002', 2], // 12650
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '84333.33', '2.53', '0.00003', 2], // 84333.3333333
        ];
    }

    /**
     * @dataProvider dataDivs
     */
    public function test_div($mode, $expect, $left, $right, $scale = null)
    {
        Decimal::setMode($mode);
        $this->assertSame($expect, Decimal::of($left)->div($right, $scale)->value());
    }

    public function dataPows() : array
    {
        return [
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1391334554.52113004524890426201', '8.21', '10'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '0.000000000718734395512932766524', '8.21', '-10'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1391334554.52', '8.21', '10', 2],

            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1391335000', '8.21', '10'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '0.0000000007187344', '8.21', '-10'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1391334554.52', '8.21', '10', 2],

            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1391334554.521130', '8.21', '10'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '0.000000', '8.21', '-10'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1391334554.52', '8.21', '10', 2],
        ];
    }

    /**
     * @dataProvider dataPows
     */
    public function test_pow($mode, $expect, $left, $right, $scale = null)
    {
        Decimal::setMode($mode);
        $this->assertSame($expect, Decimal::of($left)->pow($right, $scale)->value());
    }

    public function dataSqrts() : array
    {
        return [
            [Decimal::MODE_AUTO_PRECISION_SCALING, '3', '9'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.414213562373095048801688724209', '2'],
            [Decimal::MODE_AUTO_PRECISION_SCALING, '1.41', '2', 2],

            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '3.0000', '9'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.4142', '2'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.414214', '2.00'],
            [Decimal::MODE_SIGNIFICANCE_ARITHMETIC, '1.41', '2', 2],

            [Decimal::MODE_FIXED_DECIMAL_PLACES, '3.000000', '9'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.414214', '2'],
            [Decimal::MODE_FIXED_DECIMAL_PLACES, '1.41', '2', 2],
        ];
    }

    /**
     * @dataProvider dataSqrts
     */
    public function test_sqrt($mode, $expect, $left, $scale = null)
    {
        Decimal::setMode($mode);
        $this->assertSame($expect, Decimal::of($left)->sqrt($scale)->value());
    }

    public function test_mod()
    {
        $this->assertSame('3', Decimal::of('123')->mod('10')->value());
        $this->assertSame('3', Decimal::of('123.45')->mod('10')->value());
        $this->assertSame('3', Decimal::of('123')->mod('10.78')->value());
        $this->assertSame('24', Decimal::of('1024')->mod('1000')->value());
    }

    public function test_powmod()
    {
        $this->assertSame('24', Decimal::of('2')->powmod('10', '100')->value());
        $this->assertSame('76', Decimal::of('2')->powmod('20', '100')->value());
    }

    public function dataFormats() : array
    {
        return [
            ['123', '123'],
            ['1,234', '1234'],
            ['1,234.67', '1234.67'],
            ['1,234,678.90', '1234678.90'],
            ['1,234,678.9', '1234678.90', true],
            ['1 234 678,90', '1234678.90', false, ',', ' '],
            ['1 234 678,9', '1234678.90', true, ',', ' '],
            ['1,000,000,000,000', '1e12'],
            ['1,000,000,000,000', '1.00e12'],
            ['1,000,000,000,000', '1.00e12', true],
            ['100.00', '1.0000e2'],
            ['100', '1.0000e2', true],
            ['0.000000000001', '1e-12'],
        ];
    }

    /**
     * @dataProvider dataFormats
     */
    public function test_format($expect, $value, $omit_zero = false, $decimal_point = '.', $thousands_separator = ',')
    {
        $this->assertSame($expect, Decimal::of($value)->format($omit_zero, $decimal_point, $thousands_separator));
    }

    public function test_min()
    {
        $this->assertSame('0.001', Decimal::min('1', '1.02', '1.1', '03', '1e-3')->value());
        $this->assertSame('0.001', Decimal::min(['1', '1.02', '1.1', '03', '1e-3'])->value());
        $this->assertSame('999999999999999983222783', Decimal::min('999999999999999983222784', '999999999999999983222783', '999999999999999983222785')->value());
    }

    public function test_max()
    {
        $this->assertSame('3', Decimal::max('1', '1.02', '1.1', '03', '1e-3')->value());
        $this->assertSame('3', Decimal::max(['1', '1.02', '1.1', '03', '1e-3'])->value());
        $this->assertSame('999999999999999983222785', Decimal::max('999999999999999983222784', '999999999999999983222783', '999999999999999983222785')->value());
    }

    public function test_toInt()
    {
        $this->assertSame(1, Decimal::of('1')->toInt());
        $this->assertSame(123, Decimal::of('123')->toInt());
        $this->assertSame(123, Decimal::of('123.45')->toInt());
        $this->assertSame(123, Decimal::of('123.98')->toInt());
        $this->assertSame(-123, Decimal::of('-123.98')->toInt());
        $this->assertSame(PHP_INT_MAX, Decimal::of(PHP_INT_MAX)->toInt());
        $this->assertSame(PHP_INT_MAX, Decimal::of(PHP_INT_MAX)->add(1)->toInt());
    }

    public function test_toFloat()
    {
        $this->assertSame(1.0, Decimal::of('1')->toFloat());
        $this->assertSame(123.0, Decimal::of('123')->toFloat());
        $this->assertSame(123.45, Decimal::of('123.45')->toFloat());
        $this->assertSame(123.98, Decimal::of('123.98')->toFloat());
        $this->assertSame(-123.98, Decimal::of('-123.98')->toFloat());
    }
}
