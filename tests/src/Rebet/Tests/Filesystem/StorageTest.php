<?php
namespace Rebet\Tests\Filesystem;

use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Filesystem\Filesystem;
use Rebet\Filesystem\Storage;
use Rebet\Tests\RebetTestCase;

class StorageTest extends RebetTestCase
{
    protected function tearDown() : void
    {
        Storage::clean();
    }

    public function test_disk()
    {
        $this->assertInstanceOf(Filesystem::class, Storage::disk('private'));
        $this->assertInstanceOf(Filesystem::class, Storage::disk('public'));

        $this->assertSame(false, Storage::disk('private')->exists('foo.txt'));
        Storage::disk('private')->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::disk('private')->exists('foo.txt'));
        $this->assertSame(false, Storage::disk('public')->exists('foo.txt'));
    }

    public function test_disk_failed()
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Unable to instantiate 'disks.nothing' in Storage. Undefined configure 'Rebet\Filesystem\Storage.disks.nothing'.");

        $this->assertInstanceOf(Filesystem::class, Storage::disk('nothing'));
    }

    public function test_clean()
    {
        Storage::private()->put('foo.txt', 'foo');
        Storage::public()->put('bar.txt', 'bar');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('bar.txt'));
        Storage::clean('private');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('bar.txt'));

        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        Storage::clean();
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('bar.txt'));
    }

    public function test_copy_failed()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("Can not copy `private:foo.txt` to `public:foo.txt`, `public:foo.txt` already exists.");

        Storage::private()->put('foo.txt', 'foo');
        Storage::public()->put('foo.txt', 'foo');
        Storage::copy('private', 'foo.txt', 'public');
    }

    public function test_copy()
    {
        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::copy('private', 'foo.txt', 'public');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('foo.txt'));
        Storage::clean();

        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::copy('private', 'foo.txt', 'public', 'bar.txt');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('bar.txt'));
        $this->assertSame('foo', Storage::public()->get('bar.txt'));
        Storage::clean();

        Storage::private()->put('dir/foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        Storage::copy('private', 'dir/foo.txt', 'public');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/foo.txt'));
        Storage::clean();

        Storage::private()->put('dir/foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        Storage::copy('private', 'dir/foo.txt', 'public', 'dir/foo/bar.txt');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/foo/bar.txt'));
        Storage::clean();

        Storage::private()->put('dir/foo.txt', 'foo');
        Storage::private()->put('dir/bar.txt', 'bar');
        Storage::private()->put('dir/sub/baz.txt', 'bar');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/bar.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/sub/baz.txt'));
        $this->assertSame(false, Storage::public()->exists('dir'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/bar.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/sub'));
        $this->assertSame(false, Storage::public()->exists('dir/sub/baz.txt'));
        Storage::copy('private', 'dir', 'public');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/bar.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/sub/baz.txt'));
        $this->assertSame(true, Storage::public()->exists('dir'));
        $this->assertSame(true, Storage::public()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/bar.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/sub'));
        $this->assertSame(true, Storage::public()->exists('dir/sub/baz.txt'));
        Storage::clean();

        Storage::private()->put('dir/foo.txt', 'foo');
        Storage::private()->put('dir/bar.txt', 'bar');
        Storage::private()->put('dir/sub/baz.txt', 'bar');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/bar.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/sub/baz.txt'));
        $this->assertSame(false, Storage::public()->exists('qux'));
        $this->assertSame(false, Storage::public()->exists('qux/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('qux/bar.txt'));
        $this->assertSame(false, Storage::public()->exists('qux/sub'));
        $this->assertSame(false, Storage::public()->exists('qux/sub/baz.txt'));
        Storage::copy('private', 'dir', 'public', 'qux');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::private()->exists('dir/bar.txt'));
        $this->assertSame(true, Storage::public()->exists('qux'));
        $this->assertSame(true, Storage::public()->exists('qux/foo.txt'));
        $this->assertSame(true, Storage::public()->exists('qux/bar.txt'));
        $this->assertSame(true, Storage::public()->exists('qux/sub'));
        $this->assertSame(true, Storage::public()->exists('qux/sub/baz.txt'));
        Storage::clean();
    }

    public function test_move()
    {
        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::move('private', 'foo.txt', 'public');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('foo.txt'));
    }

    public function test_publish()
    {
        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::publish('foo.txt');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('foo.txt'));
        $this->assertSame('foo', Storage::public()->get('foo.txt'));
        Storage::clean();

        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::publish('foo.txt', 'dir/bar.txt');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/bar.txt'));
        $this->assertSame('foo', Storage::public()->get('dir/bar.txt'));
        Storage::clean();
    }
}
