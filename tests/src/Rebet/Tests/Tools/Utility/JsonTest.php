<?php
namespace Rebet\Tests\Tools\Utility;

use App\Enum\Gender;
use App\Stub\IteratorAggregateStub;
use App\Stub\JsonSerializableStub;
use App\Stub\ToArrayStub;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Json;

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

    public function test_digest()
    {
        $this->assertSame(Json::digest('sha256', 1), Json::digest('sha256', 1));
        $this->assertNotSame(Json::digest('sha256', 1), Json::digest('sha256', 2));
        $this->assertNotSame(Json::digest('sha256', 1), Json::digest('sha256', 1, 2));
        $this->assertSame(Json::digest('sha256', 1, 2), Json::digest('sha256', 1, 2));
    }
}
