<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Path;

use Rebet\Tests\RebetTestCase;

class PathTest extends RebetTestCase
{
    public function test_normalizePath()
    {
        $this->assertSame('var/www/app', Path::normalize('var/www/app'));
        $this->assertSame('/var/www/app', Path::normalize('/var/www/app'));
        $this->assertSame('/var/www/app', Path::normalize('/var/www/app/'));
        $this->assertSame('c:/var/www/app', Path::normalize('c:\\var\\www\\app'));
        $this->assertSame('c:/var/www/app', Path::normalize('c:\\var\\www\\app\\'));
        $this->assertSame('vfs://var/www/app', Path::normalize('vfs://var/www/app'));
        $this->assertSame('vfs://var/www/app', Path::normalize('vfs://var/www/app/'));

        $this->assertSame('var/www/app', Path::normalize('./var/www/app'));
        $this->assertSame('../var/www/app', Path::normalize('../var/www/app'));
        $this->assertSame('../www/app', Path::normalize('var/../../www/app'));
        $this->assertSame('../www/app', Path::normalize('/var/../../www/app'));
        $this->assertSame('../../www/app', Path::normalize('var/../..///.//../www/app'));
        $this->assertSame('app', Path::normalize('var/../www/../app'));
        $this->assertSame('www', Path::normalize('var/../www'));
        $this->assertSame('/www', Path::normalize('/var/../www'));
        $this->assertSame('.', Path::normalize('var/..'));
        $this->assertSame('/', Path::normalize('/var/..'));
        $this->assertSame('c:/', Path::normalize('c:/var/..'));
        $this->assertSame('file://', Path::normalize('file://var/..'));
        $this->assertSame('file://c:/', Path::normalize('file://c:/var/..'));
    }

    public function test_normalizePath_invalid()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid path format: c:/invalid/../../path");

        $this->assertSame('app', Path::normalize('c:/invalid/../../path'));
    }
}
