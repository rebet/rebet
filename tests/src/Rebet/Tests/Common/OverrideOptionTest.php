<?php
namespace Rebet\Tests\Common;

use Rebet\Common\OverrideOption;
use Rebet\Tests\RebetTestCase;

class OverrideOptionTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    /**
     * @dataProvider dataSplits
     */
    public function test_split($value, $expect)
    {
        $this->assertSame($expect, OverrideOption::split($value));
    }

    public function dataSplits() : array
    {
        return [
            ['', ['', null]],
            ['key', ['key', null]],
            ['key!', ['key', '!']],
            ['key<', ['key', '<']],
            ['key>', ['key', '>']],
            ['key!!', ['key!', '!']],
            ['key!<', ['key!', '<']],
            ['key!>', ['key!', '>']],
            ['key+', ['key+', null]],
            ['!', ['', '!']],
            ['<', ['', '<']],
            ['>', ['', '>']],
        ];
    }
}
