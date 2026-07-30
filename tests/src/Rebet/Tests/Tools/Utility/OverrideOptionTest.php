<?php
namespace Rebet\Tests\Tools\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Utility\OverrideOption;

class OverrideOptionTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    #[DataProvider('dataSplits')]
    public function test_split($value, $expect)
    {
        $this->assertSame($expect, OverrideOption::split($value));
    }

    public static function dataSplits() : array
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
