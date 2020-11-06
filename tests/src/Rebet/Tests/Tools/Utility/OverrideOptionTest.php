<?php
namespace Rebet\Tests\Tools\Utility;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Utility\OverrideOption;

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
            ['key+', ['key', '+']],
            ['key=', ['key', '=']],
            ['key<', ['key', '<']],
            ['key>', ['key', '>']],
            ['key==', ['key=', '=']],
            ['key=<', ['key=', '<']],
            ['key=>', ['key=', '>']],
            ['key=+', ['key=', '+']],
            ['key-', ['key-', null]],
            ['+', ['', '+']],
            ['=', ['', '=']],
            ['<', ['', '<']],
            ['>', ['', '>']],
        ];
    }
}
