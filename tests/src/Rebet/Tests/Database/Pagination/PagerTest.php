<?php

declare(strict_types=1);

namespace Rebet\Tests\Database\Pagination;

use Rebet\Database\Pagination\Pager;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;

class PagerTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(Pager::class, new Pager());
    }

    public function test_resolve(): void
    {
        $this->assertInstanceOf(Pager::class, Pager::resolve());
        $this->assertSame(1, Pager::resolve()->page());
        Config::application([
            Pager::class => [
                'resolver' => fn(Pager $pager) => $pager->page(3),
            ],
        ]);
        $this->assertSame(3, Pager::resolve()->page());
    }

    public function test_size(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(10, $pager->size());
        $this->assertSame(3, $pager->size(3)->size());
    }

    public function test_page(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(1, $pager->page());
        $this->assertSame(3, $pager->page(3)->page());
    }

    public function test_eachSide(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(0, $pager->eachSide());
        $this->assertSame(3, $pager->eachSide(3)->eachSide());
    }

    public function test_needTotal(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(false, $pager->needTotal());
        $this->assertSame(true, $pager->needTotal(true)->needTotal());
    }

    public function test_cursor(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(null, $pager->cursor());
        $this->assertSame('name', $pager->cursor('name')->cursor());
    }

    public function test_useCursor(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(false, $pager->useCursor());
        $this->assertSame(true, $pager->cursor('name')->useCursor());
    }

    public function test_next(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(1, $pager->page());
        $this->assertSame(2, $pager->next()->page());
        $this->assertSame(6, $pager->next(5)->page());
    }

    public function test_prev(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(1, $pager->page());
        $this->assertSame(1, $pager->prev()->page());
        $this->assertSame(10, $pager->next(10)->prev()->page());
    }

    public function test_verify(): void
    {
        $pager = Pager::resolve();
        $this->assertSame(false, $pager->verify(null));
        $this->assertSame(true, $pager->verify($pager));
        $this->assertSame(true, $pager->verify($pager->page(123)));
        $this->assertSame(true, $pager->verify($pager->prev()));
        $this->assertSame(true, $pager->verify($pager->next()));
        $this->assertSame(false, $pager->verify($pager->next()->eachSide(3)));
        $this->assertSame(false, $pager->verify($pager->next()->needTotal(true)));
        $this->assertSame(false, $pager->verify($pager->next()->size(25)));
        $this->assertSame(false, $pager->verify($pager->next()->cursor('unittest')));
    }
}
