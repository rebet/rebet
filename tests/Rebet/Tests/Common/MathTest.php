<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Math;
use Rebet\Tests\RebetTestCase;

class MathTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        Math::setDefaultPrecision(null);
    }

    public function test_scaleOf()
    {
        $this->assertSame(0, Math::scaleOf(''));
        $this->assertSame(0, Math::scaleOf('0'));
        $this->assertSame(0, Math::scaleOf('012'));
        $this->assertSame(0, Math::scaleOf('0.'));
        $this->assertSame(1, Math::scaleOf('0.0'));
        $this->assertSame(3, Math::scaleOf('0.000'));
        $this->assertSame(3, Math::scaleOf('-0.000'));
    }
    
    public function test_significantFigureOf()
    {
        $this->assertSame(null, Math::significantFigureOf(''));
        $this->assertSame(null, Math::significantFigureOf('0'));
        $this->assertSame(2, Math::significantFigureOf('012'));
        $this->assertSame(null, Math::significantFigureOf('0.'));
        $this->assertSame(null, Math::significantFigureOf('0.0000'));
        $this->assertSame(2, Math::significantFigureOf('1.0'));
        $this->assertSame(3, Math::significantFigureOf('1.01'));
        $this->assertSame(1, Math::significantFigureOf('0.01'));
        $this->assertSame(1, Math::significantFigureOf('0.0001'));
        $this->assertSame(4, Math::significantFigureOf('-1.000'));
        $this->assertSame(2, Math::significantFigureOf('-0.0012'));
    }

    public function test_setDefaultPrecision()
    {
        $this->assertSame('3', Math::add('1', '2'));
        $this->assertSame('-1', Math::sub('1', '2'));
        $this->assertSame('2.0', Math::mul('1', '2'));
        $this->assertSame('0.50', Math::div('1', '2'));
        $this->assertSame('123', Math::floor('123'));
        $this->assertSame('123', Math::round('123'));
        $this->assertSame('123', Math::ceil('123'));
        $this->assertSame('123', Math::format('123'));

        Math::setDefaultPrecision(3);

        $this->assertSame('3.000', Math::add('1', '2'));
        $this->assertSame('-1.000', Math::sub('1', '2'));
        $this->assertSame('2.000', Math::mul('1', '2'));
        $this->assertSame('0.500', Math::div('1', '2'));
        $this->assertSame('123.000', Math::floor('123'));
        $this->assertSame('123.000', Math::round('123'));
        $this->assertSame('123.000', Math::ceil('123'));
        $this->assertSame('123.000', Math::format('123'));
    }

    public function test_add()
    {
        $this->assertSame('3', Math::add('1', '2'));
        $this->assertSame('3.0', Math::add('1.0', '2'));
        $this->assertSame('3.32', Math::add('1.1', '2.22'));
        $this->assertSame('123.456', Math::add('123', '0.456'));
        $this->assertSame('2.53', Math::add('2', '0.53'));
        $this->assertSame('1.12', Math::add('-1.1', '2.22'));
        $this->assertSame('-1.12', Math::add('1.1', '-2.22'));

        $this->assertSame('3.00', Math::add('1', '2', 2));
        $this->assertSame('123.46', Math::add('123', '0.456', 2));
    }

    public function test_sub()
    {
        $this->assertSame('-1', Math::sub('1', '2'));
        $this->assertSame('-1.0', Math::sub('1.0', '2'));
        $this->assertSame('-1.12', Math::sub('1.1', '2.22'));
        $this->assertSame('122.544', Math::sub('123', '0.456'));
        $this->assertSame('1.47', Math::sub('2', '0.53'));
        $this->assertSame('-3.32', Math::sub('-1.1', '2.22'));
        $this->assertSame('3.32', Math::sub('1.1', '-2.22'));

        $this->assertSame('-1.00', Math::sub('1', '2', 2));
        $this->assertSame('122.54', Math::sub('123', '0.456', 2));
    }

    public function test_mul()
    {
        $this->assertSame('0.000', Math::mul('1.1', '0'));
        $this->assertSame('2.0', Math::mul('1', '2'));
        $this->assertSame('2.00', Math::mul('1.0', '2'));
        $this->assertSame('2.442', Math::mul('1.1', '2.22'));
        $this->assertSame('1.06', Math::mul('2', '0.53'));
        $this->assertSame('0.00051', Math::mul('2.53', '0.0002'));

        $this->assertSame('0.00', Math::mul('1.1', '0', 2));
        $this->assertSame('2.00', Math::mul('1', '2', 2));
        $this->assertSame('2.00', Math::mul('1.0', '2', 2));
        $this->assertSame('2.44', Math::mul('1.1', '2.22', 2));
        $this->assertSame('1.06', Math::mul('2', '0.53', 2));
        $this->assertSame('0.00', Math::mul('2.53', '0.0002', 2));
    }

    public function test_div()
    {
        $this->assertSame('0.50', Math::div('1', '2'));
        $this->assertSame('0.500', Math::div('1.0', '2'));
        $this->assertSame('0.4955', Math::div('1.1', '2.22')); // 0.49549549549
        $this->assertSame('3.77', Math::div('2', '0.53')); // 3.77358490566
        $this->assertSame('13000', Math::div('2.53', '0.0002')); // 12650
        $this->assertSame('84000', Math::div('2.53', '0.00003')); // 84333.3333333

        $this->assertSame('0.50', Math::div('1', '2', 2));
        $this->assertSame('0.50', Math::div('1.0', '2', 2));
        $this->assertSame('0.50', Math::div('1.1', '2.22', 2)); // 0.49549549549
        $this->assertSame('3.77', Math::div('2', '0.53', 2)); // 3.77358490566
        $this->assertSame('12650.00', Math::div('2.53', '0.0002', 2)); // 12650
        $this->assertSame('84333.33', Math::div('2.53', '0.00003', 2)); // 84333.3333333
    }

    public function test_comp()
    {
        $this->assertSame(0, Math::comp('1', '1'));
        $this->assertSame(0, Math::comp('1', '1.0'));
        $this->assertSame(0, Math::comp('1', '1.01', 1));
        $this->assertSame(-1, Math::comp('1', '1.01'));
        $this->assertSame(1, Math::comp('1', '0.99'));
        $this->assertSame(-1, Math::comp('1', '1.00000000000000001'));
        $this->assertSame(1, Math::comp('0.00001', '0'));
        $this->assertSame(0, Math::comp('0.00001', '0', 3));
    }

    public function test_eq()
    {
        $this->assertSame(false, Math::eq('1', '0.9'));
        $this->assertSame(true, Math::eq('1', '1.0'));
        $this->assertSame(false, Math::eq('1', '1.1'));

        $this->assertSame(false, Math::eq('1', '0.9', 0));
        $this->assertSame(true, Math::eq('1', '1.0', 0));
        $this->assertSame(true, Math::eq('1', '1.1', 0));
    }

    public function test_lt()
    {
        $this->assertSame(false, Math::lt('1', '0.9'));
        $this->assertSame(false, Math::lt('1', '1.0'));
        $this->assertSame(true, Math::lt('1', '1.1'));

        $this->assertSame(false, Math::lt('1', '0.9', 0));
        $this->assertSame(false, Math::lt('1', '1.0', 0));
        $this->assertSame(false, Math::lt('1', '1.1', 0));
    }

    public function test_lte()
    {
        $this->assertSame(false, Math::lte('1', '0.9'));
        $this->assertSame(true, Math::lte('1', '1.0'));
        $this->assertSame(true, Math::lte('1', '1.1'));

        $this->assertSame(false, Math::lte('1', '0.9', 0));
        $this->assertSame(true, Math::lte('1', '1.0', 0));
        $this->assertSame(true, Math::lte('1', '1.1', 0));
    }

    public function test_gte()
    {
        $this->assertSame(true, Math::gte('1', '0.9'));
        $this->assertSame(true, Math::gte('1', '1.0'));
        $this->assertSame(false, Math::gte('1', '1.1'));

        $this->assertSame(true, Math::gte('1', '0.9', 0));
        $this->assertSame(true, Math::gte('1', '1.0', 0));
        $this->assertSame(true, Math::gte('1', '1.1', 0));
    }

    public function test_gt()
    {
        $this->assertSame(true, Math::gt('1', '0.9'));
        $this->assertSame(false, Math::gt('1', '1.0'));
        $this->assertSame(false, Math::gt('1', '1.1'));

        $this->assertSame(true, Math::gt('1', '0.9', 0));
        $this->assertSame(false, Math::gt('1', '1.0', 0));
        $this->assertSame(false, Math::gt('1', '1.1', 0));
    }

    public function test_isNegative()
    {
        $this->assertTrue(Math::isNegative('-123.1'));
        $this->assertTrue(Math::isNegative('-1'));
        $this->assertTrue(Math::isNegative('-0.1'));
        $this->assertTrue(Math::isNegative('-0.00000000000001'));
        $this->assertFalse(Math::isNegative('-0.0'));
        $this->assertFalse(Math::isNegative('-0'));
        $this->assertFalse(Math::isNegative('0'));
        $this->assertFalse(Math::isNegative('0.1'));
        $this->assertFalse(Math::isNegative('1'));
        $this->assertFalse(Math::isNegative('123.1'));
    }

    public function test_floor()
    {
        $this->assertSame('123', Math::floor('123'));
        $this->assertSame('123', Math::floor('123.444444444'));
        $this->assertSame('123', Math::floor('123.555555555'));
        $this->assertSame('123', Math::floor('123.000000001'));
        $this->assertSame('-123', Math::floor('-123'));

        $this->assertSame('123.0', Math::floor('123', 1));
        $this->assertSame('123.4', Math::floor('123.444444444', 1));
        $this->assertSame('123.5', Math::floor('123.555555555', 1));
        $this->assertSame('123.0', Math::floor('123.000000001', 1));
        $this->assertSame('-123.5', Math::floor('-123.444444444', 1));
        
        $this->assertSame('123.00', Math::floor('123', 2));
        $this->assertSame('123.44', Math::floor('123.444444444', 2));
        $this->assertSame('123.55', Math::floor('123.555555555', 2));
        $this->assertSame('123.00', Math::floor('123.000000001', 2));
        $this->assertSame('-123.45', Math::floor('-123.444444444', 2));
        
        $this->assertSame('120', Math::floor('123', -1));
        $this->assertSame('120', Math::floor('123.444444444', -1));
        $this->assertSame('120', Math::floor('123.555555555', -1));
        $this->assertSame('120', Math::floor('123.000000001', -1));
        $this->assertSame('-130', Math::floor('-123.444444444', -1));
        
        $this->assertSame('100', Math::floor('123', -2));
        $this->assertSame('0', Math::floor('123', -3));
        $this->assertSame('0', Math::floor('123', -4));
        $this->assertSame('-200', Math::floor('-123', -2));
        $this->assertSame('-1000', Math::floor('-123', -3));
        $this->assertSame('-10000', Math::floor('-123', -4));
    }

    public function test_round()
    {
        $this->assertSame('123', Math::round('123'));
        $this->assertSame('123', Math::round('123.444444444'));
        $this->assertSame('124', Math::round('123.555555555'));
        $this->assertSame('123', Math::round('123.000000001'));
        $this->assertSame('-123', Math::round('-123.444444444'));
        $this->assertSame('-124', Math::round('-123.555555555'));

        $this->assertSame('123.0', Math::round('123', 1));
        $this->assertSame('123.4', Math::round('123.444444444', 1));
        $this->assertSame('123.6', Math::round('123.555555555', 1));
        $this->assertSame('123.0', Math::round('123.000000001', 1));
        $this->assertSame('-123.4', Math::round('-123.444444444', 1));
        $this->assertSame('-123.6', Math::round('-123.555555555', 1));

        $this->assertSame('123.00', Math::round('123', 2));
        $this->assertSame('123.44', Math::round('123.444444444', 2));
        $this->assertSame('123.56', Math::round('123.555555555', 2));
        $this->assertSame('123.00', Math::round('123.000000001', 2));
        $this->assertSame('-123.44', Math::round('-123.444444444', 2));
        $this->assertSame('-123.56', Math::round('-123.555555555', 2));

        $this->assertSame('120', Math::round('123', -1));
        $this->assertSame('120', Math::round('123.444444444', -1));
        $this->assertSame('120', Math::round('123.555555555', -1));
        $this->assertSame('120', Math::round('123.000000001', -1));
        $this->assertSame('460', Math::round('456', -1));
        $this->assertSame('-120', Math::round('-123', -1));
        $this->assertSame('-460', Math::round('-456', -1));

        $this->assertSame('100', Math::round('123', -2));
        $this->assertSame('500', Math::round('456', -2));
        $this->assertSame('600', Math::round('567', -2));
        $this->assertSame('0', Math::round('123', -3));
        $this->assertSame('0', Math::round('456', -3));
        $this->assertSame('1000', Math::round('567', -3));
        $this->assertSame('0', Math::round('567', -4));
        $this->assertSame('-100', Math::round('-123', -2));
        $this->assertSame('-500', Math::round('-456', -2));
        $this->assertSame('-600', Math::round('-567', -2));
        $this->assertSame('0', Math::round('-123', -3));
        $this->assertSame('0', Math::round('-456', -3));
        $this->assertSame('-1000', Math::round('-567', -3));
        $this->assertSame('0', Math::round('-567', -4));

        $this->assertSame('3456.0', Math::round('3456', 5, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('3456', Math::round('3456', 4, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('3460', Math::round('3456', 3, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('3500', Math::round('3456', 2, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('3000', Math::round('3456', 1, Math::TYPE_SIGNIFICANT_FIGURE));

        $this->assertSame('0.034560', Math::round('0.03456', 5, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('0.03456', Math::round('0.03456', 4, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('0.0346', Math::round('0.03456', 3, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('0.035', Math::round('0.03456', 2, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('0.03', Math::round('0.03456', 1, Math::TYPE_SIGNIFICANT_FIGURE));

        $this->assertSame('1.0346', Math::round('1.03456', 5, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('1.035', Math::round('1.03456', 4, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('1.03', Math::round('1.03456', 3, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('1.0', Math::round('1.03456', 2, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('1', Math::round('1.03456', 1, Math::TYPE_SIGNIFICANT_FIGURE));

        $this->assertSame('123.0346', Math::round('123.03456', 7, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('123.035', Math::round('123.03456', 6, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('123.03', Math::round('123.03456', 5, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('123.0', Math::round('123.03456', 4, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('123', Math::round('123.03456', 3, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('120', Math::round('123.03456', 2, Math::TYPE_SIGNIFICANT_FIGURE));
        $this->assertSame('100', Math::round('123.03456', 1, Math::TYPE_SIGNIFICANT_FIGURE));
    }
    
    public function test_ceil()
    {
        $this->assertSame('123', Math::ceil('123'));
        $this->assertSame('124', Math::ceil('123.444444444'));
        $this->assertSame('124', Math::ceil('123.555555555'));
        $this->assertSame('124', Math::ceil('123.000000001'));
        $this->assertSame('-123', Math::ceil('-123'));
        $this->assertSame('-123', Math::ceil('-123.1'));

        $this->assertSame('123.0', Math::ceil('123', 1));
        $this->assertSame('123.5', Math::ceil('123.444444444', 1));
        $this->assertSame('123.6', Math::ceil('123.555555555', 1));
        $this->assertSame('123.1', Math::ceil('123.000000001', 1));
        $this->assertSame('-123.4', Math::ceil('-123.444444444', 1));

        $this->assertSame('123.00', Math::ceil('123', 2));
        $this->assertSame('123.45', Math::ceil('123.444444444', 2));
        $this->assertSame('123.56', Math::ceil('123.555555555', 2));
        $this->assertSame('123.01', Math::ceil('123.000000001', 2));
        $this->assertSame('-123.44', Math::ceil('-123.444444444', 2));

        $this->assertSame('130', Math::ceil('123', -1));
        $this->assertSame('130', Math::ceil('123.444444444', -1));
        $this->assertSame('130', Math::ceil('123.555555555', -1));
        $this->assertSame('130', Math::ceil('123.000000001', -1));
        $this->assertSame('-120', Math::ceil('-123.444444444', -1));

        $this->assertSame('200', Math::ceil('123', -2));
        $this->assertSame('1000', Math::ceil('123', -3));
        $this->assertSame('10000', Math::ceil('123', -4));
        $this->assertSame('-100', Math::ceil('-123', -2));
        $this->assertSame('0', Math::ceil('-123', -3));
        $this->assertSame('0', Math::ceil('-123', -4));
        $this->assertSame('0', Math::ceil('-123', -3));
    }

    public function test_format()
    {
        $this->assertSame('123', Math::format('123'));
        $this->assertSame('-123', Math::format('-123'));
        $this->assertSame('1,234', Math::format('1234'));
        $this->assertSame('-1,234', Math::format('-1234'));
        $this->assertSame('1,234.123', Math::format('1234.123', 3));
        $this->assertSame('1,234,567.00', Math::format('1234567.00', 2));
        $this->assertSame('1,234,567.46', Math::format('1234567.456', 2));
        $this->assertSame('-1,234,567.46', Math::format('-1234567.456', 2));
        $this->assertSame('-1,234,567.123457', Math::format('-1234567.1234567', 6));
    }
}
