<?php
namespace Rebet\Tests\Database\Pagination;

use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\DateTime\DateTime;
use Rebet\Tests\RebetTestCase;

class CursorTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Cursor::class, new Cursor(Pager::resolve(), ['user_id' => 123]));
    }

    public function test_create()
    {
        DateTime::setTestNow('2001-02-03 04:05:06');
        $now   = DateTime::now();
        $pager = Pager::resolve()->eachSide(3);

        $this->assertEquals(
            new Cursor($pager, ['create_at' => $now, 'user_id' => 123], $pager->eachSide()),
            Cursor::create(['create_at' => 'desc', 'user_id' => 'asc'], $pager, ['create_at' => $now, 'user_id' => 123], $pager->eachSide())
        );
    }

    public function test_expired()
    {
        $pager  = Pager::resolve();
        $cursor = new Cursor($pager, ['user_id' => 123], $pager->eachSide());
        $this->assertFalse($cursor->expired());

        DateTime::setTestNow('2001-01-01 00:00:00');
        $cursor = new Cursor($pager, ['user_id' => 123], $pager->eachSide());
        DateTime::setTestNow('2001-01-01 00:30:00');
        $this->assertFalse($cursor->expired());
        DateTime::setTestNow('2001-01-01 01:00:01');
        $this->assertTrue($cursor->expired());
    }

    public function test_pager()
    {
        $pager  = Pager::resolve();
        $cursor = new Cursor($pager, ['user_id' => 123], $pager->eachSide());
        $this->assertSame($pager, $cursor->pager());
    }

    public function test_saveAndLoad()
    {
        $this->assertEquals(null, Cursor::load('unittest'));

        DateTime::setTestNow('2001-01-01 00:00:00');
        $pager  = Pager::resolve()->cursor('unittest');
        $cursor = new Cursor($pager, ['user_id' => 123], $pager->eachSide());
        $this->assertInstanceOf(Cursor::class, $cursor->save());
        $this->assertEquals($cursor, Cursor::load('unittest'));
        DateTime::setTestNow('2001-01-01 01:00:00');
        $this->assertEquals($cursor, Cursor::load('unittest'));
        DateTime::setTestNow('2001-01-01 01:00:01');
        $this->assertEquals(null, Cursor::load('unittest'));

        $pager  = Pager::resolve();
        $cursor = new Cursor($pager, ['user_id' => 123], $pager->eachSide());
        $this->assertInstanceOf(Cursor::class, $cursor->save());
        $this->assertEquals(null, Cursor::load(''));
    }
}
