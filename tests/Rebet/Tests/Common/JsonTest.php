<?php
namespace Rebet\Tests\Validation;

use Rebet\Common\Json;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\Mock\IteratorAggregateStub;
use Rebet\Tests\Mock\JsonSerializableStub;
use Rebet\Tests\Mock\ToArrayStub;
use Rebet\Tests\RebetTestCase;

class JsonTest extends RebetTestCase
{
    /**
     * @dataProvider dataSerializes
     */
    public function test_serialize($value, $expect)
    {
        $this->assertSame($expect, Json::serialize($value));
    }

    public function dataSerializes() : array
    {
        return [
            [null, null],
            ['', ''],
            [[], []],
            [0, 0],
            [123, 123],
            ['a', 'a'],
            [[1, 2, 3], [1, 2, 3]],
            [new \ArrayObject([1, 2, 3]), [1, 2, 3]],
            [new \ArrayObject([1, 2, 3, new \ArrayObject(['a', 'b'])]), [1, 2, 3, ['a', 'b']]],
            [DateTime::createDateTime('2010/01/02 12:34:56'), '2010-01-02 12:34:56'],
            [Gender::MALE(), 1],
            [Gender::FEMALE(), 2],
            [new ToArrayStub([1, 2, 3]), [1, 2, 3]],
            [new JsonSerializableStub('foo'), 'foo'],
            [new IteratorAggregateStub([1, 2, 3]), [1, 2, 3]],
        ];
    }
}
