<?php
namespace Rebet\Tests\Database\Pagination\Storage;

use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Storage\ArrayCursorStorage;
use Rebet\Database\Pagination\Storage\CursorStorage;
use Rebet\Tests\RebetTestCase;

class ArrayCursorStorageTest extends RebetTestCase
{
    /**
     * @var CursorStorage
     */
    protected $strage;

    protected function setUp() : void
    {
        $this->strage = new ArrayCursorStorage();
    }

    public function test_saveAndLoadAndRemove()
    {
        $cursor = Cursor::create(['user_id' => 'asc'], new Pager(), ['user_id' => 12], 3);
        $this->assertNull($this->strage->load('user:search'));
        $this->strage->save('user:search', $cursor);
        $this->assertEquals($cursor, $this->strage->load('user:search'));
        $this->assertNull($this->strage->load('article:search'));
        $this->strage->remove('user:search');
        $this->assertNull($this->strage->load('user:search'));

        $this->strage->save('user:search', $cursor);
        $this->assertEquals($cursor, $this->strage->load('user:search'));
        $this->strage->clear();
        $this->assertNull($this->strage->load('user:search'));
    }
}
