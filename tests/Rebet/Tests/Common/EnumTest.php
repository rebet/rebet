<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Enum;

class EnumTest extends RebetTestCase
{
    private $male;
    private $female;

    public function setUp()
    {
        $this->male   = EnumTest_Gender::MALE();
        $this->female = EnumTest_Gender::FEMALE();
    }

    public function test_callStatic()
    {
        $this->assertInstanceOf(EnumTest_Gender::class, $this->male);
        $this->assertSame(1, $this->male->value);
        $this->assertSame('男性', $this->male->label);

        $male2 = EnumTest_Gender::MALE();
        $this->assertSame($this->male, $male2);

        $status = EnumTest_AcceptStatus::ACCEPTED();
        $this->assertSame('A', $status->value);
        $this->assertSame('受理', $status->label);
        $this->assertSame('green', $status->color);
        $this->assertSame('fas fa-check-circle', $status->icon);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid enum const. Rebet\Tests\Common\EnumTest_Gender::INVALID is not defined.
     */
    public function test_callStatic_undefine()
    {
        $invalid = EnumTest_Gender::INVALID();
    }
    
    public function test_equals()
    {
        $male2 = EnumTest_Gender::MALE();

        $this->assertTrue($this->male->equals(1));
        $this->assertTrue($this->male->equals('1'));
        $this->assertTrue($this->male->equals($this->male));
        $this->assertTrue($this->male->equals($male2));

        $this->assertFalse($this->male->equals(2));
        $this->assertFalse($this->male->equals('2'));
        $this->assertFalse($this->male->equals($this->female));
    }
    
    public function test_in()
    {
        $this->assertTrue($this->male->in(1, 2));
        $this->assertTrue($this->male->in('1', '2'));
        $this->assertTrue($this->male->in(...EnumTest_Gender::lists()));
        
        $this->assertFalse($this->male->in(2, 3, 4));
        $this->assertFalse($this->male->in($this->female));
    }
    
    public function test_toString()
    {
        $this->assertSame('男性', "{$this->male}");
    }
    
    public function test_jsonSerialize()
    {
        $this->assertSame(1, $this->male->jsonSerialize());
    }

    public function test_lists()
    {
        $this->assertSame(
            [
                EnumTest_Gender::MALE(),
                EnumTest_Gender::FEMALE(),
            ],
            EnumTest_Gender::lists()
        );

        $this->assertSame(
            [
                EnumTest_AcceptStatus::WAITING(),
                EnumTest_AcceptStatus::ACCEPTED(),
                EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::lists()
        );
    }

    public function test_maps()
    {
        $this->assertSame(
            [
                1 => EnumTest_Gender::MALE(),
                2 => EnumTest_Gender::FEMALE(),
            ],
            EnumTest_Gender::maps()
        );

        $this->assertSame(
            [
                '男性' => EnumTest_Gender::MALE(),
                '女性' => EnumTest_Gender::FEMALE(),
            ],
            EnumTest_Gender::maps('label')
        );

        $this->assertSame(
            [
                'W' => EnumTest_AcceptStatus::WAITING(),
                'A' => EnumTest_AcceptStatus::ACCEPTED(),
                'R' => EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::maps()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid property access. Property Rebet\Tests\Common\EnumTest_Gender->invalid is not exists.
     */
    public function test_maps_invalid()
    {
        EnumTest_Gender::maps('invalid');
        $this->fail("Never execute.");
    }

    public function test_fieldOf()
    {
        $this->assertSame(EnumTest_Gender::MALE(), EnumTest_Gender::fieldOf('value', 1));
        $this->assertSame(EnumTest_Gender::MALE(), EnumTest_Gender::fieldOf('label', '男性'));
        $this->assertSame(EnumTest_Gender::FEMALE(), EnumTest_Gender::fieldOf('label', '女性'));
        $this->assertNull(EnumTest_Gender::fieldOf('value', 3));
        
        $this->assertSame(EnumTest_AcceptStatus::REJECTED(), EnumTest_AcceptStatus::fieldOf('value', 'R'));
        $this->assertSame(EnumTest_AcceptStatus::ACCEPTED(), EnumTest_AcceptStatus::fieldOf('color', 'green'));
        $this->assertNull(EnumTest_AcceptStatus::fieldOf('value', 1));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid property access. Property Rebet\Tests\Common\EnumTest_Gender->invalid is not exists.
     */
    public function test_fieldOf_invalid()
    {
        $this->assertNull(EnumTest_Gender::fieldOf('invalid', 1));
        $this->fail("Never execute.");
    }

    public function test_valueOf()
    {
        $this->assertSame(EnumTest_Gender::MALE(), EnumTest_Gender::valueOf(1));
        $this->assertSame(EnumTest_Gender::MALE(), EnumTest_Gender::valueOf('1'));
        $this->assertSame(EnumTest_AcceptStatus::REJECTED(), EnumTest_AcceptStatus::valueOf('R'));
        $this->assertSame(EnumTest_CODE::NO_2(), EnumTest_CODE::valueOf('02'));

        $this->assertSame(null, EnumTest_Gender::valueOf('01'));
        $this->assertSame(null, EnumTest_CODE::valueOf(2));
    }

    public function test_convertTo()
    {
        $gender = EnumTest_Gender::MALE();
        $this->assertSame(1, $gender->convertTo('int'));
        $this->assertSame(null, $gender->convertTo('float'));
        $this->assertSame('1', $gender->convertTo('string'));
        $this->assertSame($gender, $gender->convertTo(EnumTest_Gender::class));
        $this->assertSame(null, $gender->convertTo(EnumTest_AcceptStatus::class));
        $this->assertSame(null, $gender->convertTo(EnumTest_Ratio::class));

        $status = EnumTest_AcceptStatus::REJECTED();
        $this->assertSame(null, $status->convertTo('int'));
        $this->assertSame(null, $status->convertTo('float'));
        $this->assertSame('R', $status->convertTo('string'));
        $this->assertSame(null, $status->convertTo(EnumTest_Gender::class));
        $this->assertSame($status, $status->convertTo(EnumTest_AcceptStatus::class));
        $this->assertSame(null, $status->convertTo(EnumTest_Ratio::class));

        $ratio = EnumTest_Ratio::HARF();
        $this->assertSame(null, $ratio->convertTo('int'));
        $this->assertSame(0.5, $ratio->convertTo('float'));
        $this->assertSame('0.5', $ratio->convertTo('string'));
        $this->assertSame(null, $ratio->convertTo(EnumTest_Gender::class));
        $this->assertSame(null, $ratio->convertTo(EnumTest_AcceptStatus::class));
        $this->assertSame($ratio, $ratio->convertTo(EnumTest_Ratio::class));
    }

    public function test_labelOf()
    {
        $this->assertSame(EnumTest_Gender::MALE(), EnumTest_Gender::labelOf('男性'));
        $this->assertSame(EnumTest_AcceptStatus::REJECTED(), EnumTest_AcceptStatus::labelOf('却下'));
    }

    public function test_listOf()
    {
        $this->assertSame(
            [1, 2],
            EnumTest_Gender::listOf('value')
        );

        $this->assertSame(
            [2],
            EnumTest_Gender::listOf('value', function ($enum) {
                return $enum->label === '女性';
            })
        );

        $this->assertSame(
            ['男性', '女性'],
            EnumTest_Gender::listOf('label')
        );

        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::listOf('value')
        );

        $this->assertSame(
            ['orange', 'green', 'red'],
            EnumTest_AcceptStatus::listOf('color')
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid property access. Property Rebet\Tests\Common\EnumTest_Gender->invalid is not exists.
     */
    public function test_listOf_invalid()
    {
        $this->assertNull(EnumTest_Gender::listOf('invalid'));
        $this->fail("Never execute.");
    }

    public function test_values()
    {
        $this->assertSame(
            [1, 2],
            EnumTest_Gender::values()
        );

        $this->assertSame(
            [2],
            EnumTest_Gender::values(function ($enum) {
                return $enum->label === '女性';
            })
        );

        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::values()
        );
    }

    public function test_labels()
    {
        $this->assertSame(
            ['男性', '女性'],
            EnumTest_Gender::labels()
        );

        $this->assertSame(
            ['男性'],
            EnumTest_Gender::labels(function ($enum) {
                return $enum->value === 1;
            })
        );

        $this->assertSame(
            ['待機中', '受理', '却下'],
            EnumTest_AcceptStatus::labels()
        );
    }

    public function test_nexts()
    {
        $this->assertSame(
            [
                EnumTest_AcceptStatus::WAITING(),
                EnumTest_AcceptStatus::ACCEPTED(),
                EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::nexts(EnumTest_AcceptStatus::WAITING(), ['role' => 'operator'])
        );

        $this->assertSame(
            [
                EnumTest_AcceptStatus::ACCEPTED(),
            ],
            EnumTest_AcceptStatus::nexts(EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'operator'])
        );

        $this->assertSame(
            [
                EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::nexts(EnumTest_AcceptStatus::REJECTED(), ['role' => 'operator'])
        );

        $this->assertSame(
            [
                EnumTest_AcceptStatus::WAITING(),
                EnumTest_AcceptStatus::ACCEPTED(),
                EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::nexts(EnumTest_AcceptStatus::WAITING(), ['role' => 'admin'])
        );

        $this->assertSame(
            [
                EnumTest_AcceptStatus::WAITING(),
                EnumTest_AcceptStatus::ACCEPTED(),
                EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::nexts(EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'admin'])
        );

        $this->assertSame(
            [
                EnumTest_AcceptStatus::WAITING(),
                EnumTest_AcceptStatus::ACCEPTED(),
                EnumTest_AcceptStatus::REJECTED(),
            ],
            EnumTest_AcceptStatus::nexts(EnumTest_AcceptStatus::REJECTED(), ['role' => 'admin'])
        );
    }

    public function test_nextOf()
    {
        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::nextOf('value', EnumTest_AcceptStatus::WAITING(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['A'],
            EnumTest_AcceptStatus::nextOf('value', EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['R'],
            EnumTest_AcceptStatus::nextOf('value', EnumTest_AcceptStatus::REJECTED(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::nextOf('value', EnumTest_AcceptStatus::WAITING(), ['role' => 'admin'])
        );

        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::nextOf('value', EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'admin'])
        );

        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::nextOf('value', EnumTest_AcceptStatus::REJECTED(), ['role' => 'admin'])
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid property access. Property Rebet\Tests\Common\EnumTest_Gender->invalid is not exists.
     */
    public function test_nextOf_invalid()
    {
        $this->assertNull(EnumTest_Gender::listOf('invalid'));
        $this->fail("Never execute.");
    }
    
    public function test_nextValues()
    {
        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::nextValues(EnumTest_AcceptStatus::WAITING(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['A'],
            EnumTest_AcceptStatus::nextValues(EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['W', 'A', 'R'],
            EnumTest_AcceptStatus::nextValues(EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'admin'])
        );
    }
    
    public function test_nextLabels()
    {
        $this->assertSame(
            ['待機中', '受理', '却下'],
            EnumTest_AcceptStatus::nextLabels(EnumTest_AcceptStatus::WAITING(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['受理'],
            EnumTest_AcceptStatus::nextLabels(EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'operator'])
        );

        $this->assertSame(
            ['待機中', '受理', '却下'],
            EnumTest_AcceptStatus::nextLabels(EnumTest_AcceptStatus::ACCEPTED(), ['role' => 'admin'])
        );
    }
}

class EnumTest_Gender extends Enum
{
    const MALE   = [1, '男性'];
    const FEMALE = [2, '女性'];
}
class EnumTest_AcceptStatus extends Enum
{
    const WAITING  = ['W', '待機中', 'orange', 'far fa-clock'];
    const ACCEPTED = ['A', '受理'  , 'green' , 'fas fa-check-circle'];
    const REJECTED = ['R', '却下'  , 'red'   , 'fas fa-times-circle'];
    
    public $color;
    public $icon;

    protected function __construct($value, $label, $color, $icon)
    {
        parent::__construct($value, $label);
        $this->color = $color;
        $this->icon  = $icon;
    }

    public static function nexts($current, ?array $context = null) : array
    {
        switch ($context['role']) {
            case 'operator':
                $current = self::valueOf($current);
                if ($current === self::WAITING()) {
                    return [self::WAITING(), self::ACCEPTED(), self::REJECTED()];
                } else {
                    return [$current];
                }
                // no break
            case 'admin':
                return self::lists();
        }

        return [];
    }
}
class EnumTest_Ratio extends Enum
{
    const FULL    = [1.0 , '100%'];
    const HARF    = [0.5 , '50%'];
    const QUARTER = [0.25, '25%'];
}
class EnumTest_Code extends Enum
{
    const NO_1 = ['01', 'No. 1'];
    const NO_2 = ['02', 'No. 2'];
    const NO_3 = ['03', 'No. 3'];
}
