<?php
namespace Rebet\Tests\Database\Pagination;

use Rebet\Database\Pagination\Paginator;
use Rebet\Tests\RebetTestCase;

class PaginatorTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Paginator::class, new Paginator([], 0, 10, 1, null, 1));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid paginator arguments. Argument total or next_page_count may not be null at least one.
     */
    public function test___construct_error()
    {
        $paginator = new Paginator([], 0, 10, 1, null, null);
        $this->assertSame(1, $paginator->nextPageCount());
    }

    public function test_count()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(0, $paginator->count());

        $paginator = new Paginator([1, 2, 3], 0, 10, 1, null, 1);
        $this->assertSame(3, $paginator->count());
    }

    public function test_total()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(null, $paginator->total());

        $paginator = new Paginator([1, 2, 3], 0, 10, 1, 0);
        $this->assertSame(0, $paginator->total());

        $paginator = new Paginator([1, 2, 3], 0, 10, 1, 100);
        $this->assertSame(100, $paginator->total());
    }

    public function test_page()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(1, $paginator->page());

        $paginator = new Paginator([], 0, 10, 0, null, 1);
        $this->assertSame(1, $paginator->page());

        $paginator = new Paginator([], 0, 10, -8, null, 1);
        $this->assertSame(1, $paginator->page());

        $paginator = new Paginator([], 0, 10, 8, null, 1);
        $this->assertSame(8, $paginator->page());

        $paginator = new Paginator([], 0, 10, 8, 50);
        $this->assertSame(5, $paginator->page());

        $paginator = new Paginator([], 0, 10, 8, 100);
        $this->assertSame(8, $paginator->page());
    }

    public function test_pageSize()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(10, $paginator->pageSize());

        $paginator = new Paginator([], 0, 0, 1, null, 1);
        $this->assertSame(1, $paginator->pageSize());

        $paginator = new Paginator([], 0, -10, 1, null, 1);
        $this->assertSame(1, $paginator->pageSize());

        $paginator = new Paginator([], 0, 25, 1, null, 1);
        $this->assertSame(25, $paginator->pageSize());
    }

    public function test_nextPageCount()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(1, $paginator->nextPageCount());

        $paginator = new Paginator([], 0, 10, 1, 50);
        $this->assertSame(4, $paginator->nextPageCount());

        $paginator = new Paginator([], 0, 10, 3, 71);
        $this->assertSame(5, $paginator->nextPageCount());

        $paginator = new Paginator([], 0, 10, 3, 21);
        $this->assertSame(0, $paginator->nextPageCount());

        $paginator = new Paginator([], 0, 10, 1, null, 3);
        $this->assertSame(3, $paginator->nextPageCount());

        $paginator = new Paginator([], 0, 10, 1, null, 0);
        $this->assertSame(0, $paginator->nextPageCount());
    }

    public function test_lastPage()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(null, $paginator->lastPage());

        $paginator = new Paginator([], 0, 10, 1, 50);
        $this->assertSame(5, $paginator->lastPage());

        $paginator = new Paginator([], 0, 10, 3, 71);
        $this->assertSame(8, $paginator->lastPage());

        $paginator = new Paginator([], 0, 10, 3, 21);
        $this->assertSame(3, $paginator->lastPage());

        $paginator = new Paginator([], 0, 10, 1, null, 3);
        $this->assertSame(null, $paginator->lastPage());

        $paginator = new Paginator([], 0, 10, 1, null, 0);
        $this->assertSame(null, $paginator->lastPage());
    }

    public function test_from()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(0, $paginator->from());

        $paginator = new Paginator([1, 2, 3], 0, 10, 1, null, 1);
        $this->assertSame(1, $paginator->from());

        $paginator = new Paginator([1, 2, 3, 4, 5, 6, 7, 8, 9, 0], 0, 10, 1, null, 1);
        $this->assertSame(1, $paginator->from());

        $paginator = new Paginator([1, 2, 3], 0, 10, 2, null, 1);
        $this->assertSame(11, $paginator->from());

        $paginator = new Paginator([1, 2, 3], 0, 12, 3, null, 1);
        $this->assertSame(25, $paginator->from());
    }

    public function test_to()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(0, $paginator->to());

        $paginator = new Paginator([1, 2, 3], 0, 10, 1, null, 1);
        $this->assertSame(3, $paginator->to());

        $paginator = new Paginator([1, 2, 3, 4, 5, 6, 7, 8, 9, 0], 0, 10, 1, null, 1);
        $this->assertSame(10, $paginator->to());

        $paginator = new Paginator([1, 2, 3], 0, 10, 2, null, 1);
        $this->assertSame(13, $paginator->to());

        $paginator = new Paginator([1, 2, 3], 0, 12, 3, null, 1);
        $this->assertSame(27, $paginator->to());
    }

    public function test_hasNext()
    {
        $paginator = new Paginator([1, 2, 3], 0, 3, 1, null, 0);
        $this->assertSame(false, $paginator->hasNext());

        $paginator = new Paginator([1, 2, 3], 0, 3, 1, null, 1);
        $this->assertSame(true, $paginator->hasNext());

        $paginator = new Paginator([1, 2, 3], 0, 3, 1, 4);
        $this->assertSame(true, $paginator->hasNext());

        $paginator = new Paginator([4], 0, 3, 2, 4);
        $this->assertSame(false, $paginator->hasNext());
    }

    public function test_hasPrev()
    {
        $paginator = new Paginator([1, 2, 3], 0, 3, 1, null, 0);
        $this->assertSame(false, $paginator->hasPrev());

        $paginator = new Paginator([1, 2, 3], 0, 3, 1, null, 1);
        $this->assertSame(false, $paginator->hasPrev());

        $paginator = new Paginator([1, 2, 3], 0, 3, 3, null, 1);
        $this->assertSame(true, $paginator->hasPrev());

        $paginator = new Paginator([1, 2, 3], 0, 3, 1, 4);
        $this->assertSame(false, $paginator->hasPrev());

        $paginator = new Paginator([4], 0, 3, 2, 4);
        $this->assertSame(true, $paginator->hasPrev());
    }

    public function test_hasTotal()
    {
        $paginator = new Paginator([1, 2, 3], 0, 3, 1, null, 0);
        $this->assertSame(false, $paginator->hasTotal());

        $paginator = new Paginator([1, 2, 3], 0, 3, 1, 10);
        $this->assertSame(true, $paginator->hasTotal());
    }

    public function test_hasLastPage()
    {
        $paginator = new Paginator([1, 2, 3], 0, 3, 1, null, 0);
        $this->assertSame(false, $paginator->hasLastPage());

        $paginator = new Paginator([1, 2, 3], 0, 3, 1, 10);
        $this->assertSame(true, $paginator->hasLastPage());
    }
}
