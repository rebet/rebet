<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Math;
use Rebet\Tests\RebetTestCase;

class MathTest extends RebetTestCase
{
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
    }
    
    public function test_ceil()
    {
        $this->assertSame('123', Math::ceil('123'));
        $this->assertSame('124', Math::ceil('123.444444444'));
        $this->assertSame('124', Math::ceil('123.555555555'));
        $this->assertSame('124', Math::ceil('123.000000001'));
        $this->assertSame('-123', Math::ceil('-123'));
        // $this->assertSame('-123', Math::ceil('-123.1'));

        // $this->assertSame('123.0', Math::ceil('123', 1));
        // $this->assertSame('123.5', Math::ceil('123.444444444', 1));
        // $this->assertSame('123.6', Math::ceil('123.555555555', 1));
        // $this->assertSame('123.0', Math::ceil('123.000000001', 1));
        // $this->assertSame('-123.3', Math::ceil('-123.444444444', 1));

        // $this->assertSame('123.00', Math::ceil('123', 2));
        // $this->assertSame('123.44', Math::ceil('123.444444444', 2));
        // $this->assertSame('123.55', Math::ceil('123.555555555', 2));
        // $this->assertSame('123.00', Math::ceil('123.000000001', 2));
        // $this->assertSame('-123.44', Math::ceil('-123.444444444', 2));

        // $this->assertSame('120', Math::ceil('123', -1));
        // $this->assertSame('120', Math::ceil('123.444444444', -1));
        // $this->assertSame('120', Math::ceil('123.555555555', -1));
        // $this->assertSame('120', Math::ceil('123.000000001', -1));
        // $this->assertSame('-120', Math::ceil('-123.444444444', -1));

        // $this->assertSame('100', Math::ceil('123', -2));
        // $this->assertSame('0', Math::ceil('123', -3));
        // $this->assertSame('0', Math::ceil('123', -4));
        // $this->assertSame('-100', Math::ceil('-123', -2));
        // $this->assertSame('0', Math::ceil('-123', -3));
    }
}
