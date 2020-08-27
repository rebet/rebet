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

    public function test_action()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertInstanceOf(Paginator::class, $paginator->action('/foo'));
        $this->assertSame('/foo', $this->inspect($paginator, 'action'));
        $this->assertSame('page', $this->inspect($paginator, 'page_name'));
        $this->assertSame(null, $this->inspect($paginator, 'anchor'));

        $this->assertInstanceOf(Paginator::class, $paginator->action('/bar', '_page', 'top'));
        $this->assertSame('/bar', $this->inspect($paginator, 'action'));
        $this->assertSame('_page', $this->inspect($paginator, 'page_name'));
        $this->assertSame('top', $this->inspect($paginator, 'anchor'));
    }

    public function test_with()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertInstanceOf(Paginator::class, $paginator->with(['gender' => 1]));
        $this->assertSame(['gender' => 1], $this->inspect($paginator, 'queries'));

        $this->assertInstanceOf(Paginator::class, $paginator->with(['foo' => 'bar']));
        $this->assertSame(['gender' => 1, 'foo' => 'bar'], $this->inspect($paginator, 'queries'));

        $this->assertInstanceOf(Paginator::class, $paginator->with(['gender' => 2]));
        $this->assertSame(['gender' => 2, 'foo' => 'bar'], $this->inspect($paginator, 'queries'));
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

    public function test_hasPages()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(true, $paginator->hasPages());

        $paginator = new Paginator([], 0, 10, 1, 50);
        $this->assertSame(true, $paginator->hasPages());

        $paginator = new Paginator([], 0, 10, 3, 71);
        $this->assertSame(true, $paginator->hasPages());

        $paginator = new Paginator([], 0, 10, 3, 21);
        $this->assertSame(true, $paginator->hasPages());

        $paginator = new Paginator([], 0, 10, 1, null, 3);
        $this->assertSame(true, $paginator->hasPages());

        $paginator = new Paginator([], 0, 10, 1, null, 0);
        $this->assertSame(false, $paginator->hasPages());
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

    public function test_prevPage()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(1, $paginator->prevPage());

        $paginator = new Paginator([], 0, 10, 0, null, 1);
        $this->assertSame(1, $paginator->prevPage());

        $paginator = new Paginator([], 0, 10, -8, null, 1);
        $this->assertSame(1, $paginator->prevPage());

        $paginator = new Paginator([], 0, 10, 8, null, 1);
        $this->assertSame(7, $paginator->prevPage());

        $paginator = new Paginator([], 0, 10, 8, 50);
        $this->assertSame(4, $paginator->prevPage());

        $paginator = new Paginator([], 0, 10, 8, 100);
        $this->assertSame(7, $paginator->prevPage());
    }

    public function test_nextPage()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(2, $paginator->nextPage());

        $paginator = new Paginator([], 0, 10, 0, null, 1);
        $this->assertSame(2, $paginator->nextPage());

        $paginator = new Paginator([], 0, 10, -8, null, 1);
        $this->assertSame(2, $paginator->nextPage());

        $paginator = new Paginator([], 0, 10, 8, null, 1);
        $this->assertSame(9, $paginator->nextPage());

        $paginator = new Paginator([], 0, 10, 8, null, 0);
        $this->assertSame(8, $paginator->nextPage());

        $paginator = new Paginator([], 0, 10, 8, 50);
        $this->assertSame(5, $paginator->nextPage());

        $paginator = new Paginator([], 0, 10, 8, 100);
        $this->assertSame(9, $paginator->nextPage());
    }

    public function test_pageUrl()
    {
        $paginator = new Paginator([], 0, 10, 5, 100);
        $this->assertSame(null, $paginator->pageUrl(1));

        $paginator->action('/foo/bar');

        $this->assertSame('/foo/bar?page=-1', $paginator->pageUrl(-1));
        $this->assertSame('/foo/bar?page=3', $paginator->pageUrl(3));
        $this->assertSame('/foo/bar?page=100', $paginator->pageUrl(100));

        $paginator->with(['gender' => 1]);
        $this->assertSame('/foo/bar?gender=1&page=3', $paginator->pageUrl(3));

        $paginator->with(['foo' => 'bar']);
        $this->assertSame('/foo/bar?gender=1&foo=bar&page=3', $paginator->pageUrl(3));

        $paginator->with(['status' => [1, 2]]);
        $this->assertSame('/foo/bar?gender=1&foo=bar&status%5B0%5D=1&status%5B1%5D=2&page=3', $paginator->pageUrl(3));

        $paginator->with(null);
        $this->assertSame('/foo/bar?page=3', $paginator->pageUrl(3));

        $paginator->action('/foo', '_page');
        $this->assertSame('/foo?_page=3', $paginator->pageUrl(3));

        $paginator->action('/foo', '_page', 'top');
        $this->assertSame('/foo?_page=3#top', $paginator->pageUrl(3));

        $paginator->with(['gender' => 1]);
        $this->assertSame('/foo?gender=1&_page=3#top', $paginator->pageUrl(3));
    }

    public function test_firstPageUrl()
    {
        $paginator = new Paginator([], 0, 10, 5, 100);
        $this->assertSame(null, $paginator->firstPageUrl());

        $paginator->action('/foo/bar');
        $this->assertSame('/foo/bar?page=1', $paginator->firstPageUrl());
    }

    public function test_prevPageUrl()
    {
        $paginator = new Paginator([], 0, 10, 5, 100);
        $this->assertSame(null, $paginator->prevPageUrl());

        $paginator->action('/foo/bar');
        $this->assertSame('/foo/bar?page=4', $paginator->prevPageUrl());

        $paginator = new Paginator([], 0, 10, 1, 100);
        $paginator->action('/foo/bar');
        $this->assertSame(null, $paginator->prevPageUrl());
    }

    public function test_nextPageUrl()
    {
        $paginator = new Paginator([], 0, 10, 5, 100);
        $this->assertSame(null, $paginator->nextPageUrl());

        $paginator->action('/foo/bar');
        $this->assertSame('/foo/bar?page=6', $paginator->nextPageUrl());

        $paginator = new Paginator([], 0, 10, 10, 100);
        $paginator->action('/foo/bar');
        $this->assertSame(null, $paginator->nextPageUrl());

        $paginator = new Paginator([], 0, 10, 3, null, 0);
        $paginator->action('/foo/bar');
        $this->assertSame(null, $paginator->nextPageUrl());
    }

    public function test_lastPageUrl()
    {
        $paginator = new Paginator([], 0, 10, 5, 100);
        $this->assertSame(null, $paginator->lastPageUrl());

        $paginator->action('/foo/bar');
        $this->assertSame('/foo/bar?page=10', $paginator->lastPageUrl());

        $paginator = new Paginator([], 0, 10, 3, null, 0);
        $paginator->action('/foo/bar');
        $this->assertSame(null, $paginator->lastPageUrl());

        $paginator = new Paginator([], 0, 10, 3, null, 3);
        $paginator->action('/foo/bar');
        $this->assertSame(null, $paginator->lastPageUrl());
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

    public function test_onFirstPage()
    {
        $paginator = new Paginator([], 0, 10, -1, null, 1);
        $this->assertSame(true, $paginator->onFirstPage());

        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(true, $paginator->onFirstPage());

        $paginator = new Paginator([], 0, 10, 2, null, 1);
        $this->assertSame(false, $paginator->onFirstPage());
    }

    public function test_onLastPage()
    {
        $paginator = new Paginator([], 0, 10, 5, null, 1);
        $this->assertSame(false, $paginator->onLastPage());

        $paginator = new Paginator([], 0, 10, 5, null, 0);
        $this->assertSame(true, $paginator->onLastPage());

        $paginator = new Paginator([], 0, 10, 9, 100);
        $this->assertSame(false, $paginator->onLastPage());

        $paginator = new Paginator([], 0, 10, 10, 100);
        $this->assertSame(true, $paginator->onLastPage());

        $paginator = new Paginator([], 0, 10, 20, 100);
        $this->assertSame(true, $paginator->onLastPage());

        $paginator = new Paginator([], 0, 10, 1, 10);
        $this->assertSame(true, $paginator->onLastPage());

        $paginator = new Paginator([], 0, 10, -1, 10);
        $this->assertSame(true, $paginator->onLastPage());
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

    public function test_eachSide()
    {
        $paginator = new Paginator([], 0, 10, 1, null, 1);
        $this->assertSame(0, $paginator->eachSide());

        $paginator = new Paginator([1, 2, 3], 3, 10, 1, null, 1);
        $this->assertSame(3, $paginator->eachSide());
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

    public function dataEachSidePages() : array
    {
        return [
            [[1]            , 0,  1,    0, null],
            [[1]            , 0,  1,    1, null],
            [[1]            , 0,  1,   11, null],
            [[2]            , 0,  2,   11, null],
            [[2]            , 0,  3,   11, null],
            [[1]            , 2,  1,    0, null],
            [[1]            , 2,  1,    1, null],
            [[1, 2]         , 2,  1,   11, null],
            [[1, 2, 3]      , 2,  1,   21, null],
            [[1, 2, 3, 4]   , 2,  1,   31, null],
            [[1, 2, 3, 4, 5], 2,  1,   41, null],
            [[1, 2, 3, 4, 5], 2,  1,   51, null],
            [[1, 2, 3, 4, 5], 2,  1,   61, null],
            [[1, 2, 3, 4, 5], 2,  2,   61, null],
            [[1, 2, 3, 4, 5], 2,  3,   61, null],
            [[2, 3, 4, 5, 6], 2,  4,   61, null],
            [[3, 4, 5, 6, 7], 2,  5,   61, null],
            [[3, 4, 5, 6, 7], 2,  6,   61, null],
            [[3, 4, 5, 6, 7], 2,  7,   61, null],
            [[3, 4, 5, 6, 7], 2,  8,   61, null],
            [[1, 2, 3, 4, 5], 2, -1,   61, null],

            [[1]            , 0,  1, null,    0],
            [[1]            , 0,  1, null,    1],
            [[1]            , 0,  1, null,    2],
            [[2]            , 0,  2, null,    1],
            [[1]            , 2,  1, null,    0],
            [[1, 2]         , 2,  1, null,    1],
            [[1, 2, 3]      , 2,  1, null,    2],
            [[1, 2, 3, 4]   , 2,  1, null,    3],
            [[1, 2, 3, 4, 5], 2,  1, null,    4],
            [[1, 2, 3, 4, 5], 2,  1, null,    5],
            [[1, 2, 3, 4, 5], 2,  1, null,    6],
            [[1, 2, 3, 4, 5], 2,  2, null,    5],
            [[1, 2, 3, 4, 5], 2,  3, null,    4],
            [[2, 3, 4, 5, 6], 2,  4, null,    3],
            [[3, 4, 5, 6, 7], 2,  5, null,    2],
            [[3, 4, 5, 6, 7], 2,  6, null,    1],
            [[3, 4, 5, 6, 7], 2,  7, null,    0],
        ];
    }

    /**
     * @dataProvider dataEachSidePages
     */
    public function test_eachSidePages(array $expect, int $each_side, int $page, ?int $total, ?int $next_page_count = null)
    {
        $paginator = new Paginator([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $each_side, 10, $page, $total, $next_page_count);
        $this->assertEquals($expect, $paginator->eachSidePages());
    }

    public function test_jsonSerialize()
    {
        $paginator = new Paginator([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 3, 10, 5, 100);
        $paginator->action('/foo')->with(['gender' => 1]);
        $this->assertEquals([
            'has_pages'      => true,
            'has_total'      => true,
            'has_prev'       => true,
            'has_next'       => true,
            'has_last_page'  => true,
            'on_first_page'  => false,
            'on_last_page'   => false,
            'total'          => 100,
            'page_size'      => 10,
            'page'           => 5,
            'prev_page'      => 4,
            'next_page'      => 6,
            'last_page'      => 10,
            'first_page_url' => '/foo?gender=1&page=1',
            'prev_page_url'  => '/foo?gender=1&page=4',
            'next_page_url'  => '/foo?gender=1&page=6',
            'last_page_url'  => '/foo?gender=1&page=10',
            'page_urls'      => [
                2 => '/foo?gender=1&page=2',
                3 => '/foo?gender=1&page=3',
                4 => '/foo?gender=1&page=4',
                5 => '/foo?gender=1&page=5',
                6 => '/foo?gender=1&page=6',
                7 => '/foo?gender=1&page=7',
                8 => '/foo?gender=1&page=8',
            ],
            'each_side'      => 3,
            'from'           => 41,
            'to'             => 50,
            'data'           => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        ], $paginator->jsonSerialize());

        $paginator = new Paginator([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 3, 10, 5, null, 2);
        $paginator->action('/foo')->with(['gender' => 1]);
        $this->assertEquals([
            'has_pages'      => true,
            'has_total'      => false,
            'has_prev'       => true,
            'has_next'       => true,
            'has_last_page'  => false,
            'on_first_page'  => false,
            'on_last_page'   => false,
            'total'          => null,
            'page_size'      => 10,
            'page'           => 5,
            'prev_page'      => 4,
            'next_page'      => 6,
            'last_page'      => null,
            'first_page_url' => '/foo?gender=1&page=1',
            'prev_page_url'  => '/foo?gender=1&page=4',
            'next_page_url'  => '/foo?gender=1&page=6',
            'last_page_url'  => null,
            'page_urls'      => [
                1 => '/foo?gender=1&page=1',
                2 => '/foo?gender=1&page=2',
                3 => '/foo?gender=1&page=3',
                4 => '/foo?gender=1&page=4',
                5 => '/foo?gender=1&page=5',
                6 => '/foo?gender=1&page=6',
                7 => '/foo?gender=1&page=7',
            ],
            'each_side'      => 3,
            'from'           => 41,
            'to'             => 50,
            'data'           => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        ], $paginator->jsonSerialize());
    }
}
