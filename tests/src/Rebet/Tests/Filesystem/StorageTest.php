<?php

declare(strict_types=1);

namespace Rebet\Tests\Filesystem;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Override;
use Rebet\Filesystem\BuiltinFilesystem;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Filesystem\Filesystem;
use Rebet\Filesystem\Storage;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Rebet\Tools\Exception\LogicException;

class StorageTest extends RebetTestCase
{
    #[Override]
    protected function tearDown(): void
    {
        Storage::reset();
        parent::tearDown();
    }

    public function test_disk(): void
    {
        $this->assertInstanceOf(Filesystem::class, Storage::disk('private'));
        $this->assertInstanceOf(Filesystem::class, Storage::disk('public'));

        $this->assertSame(false, Storage::disk('private')->exists('foo.txt'));
        Storage::disk('private')->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::disk('private')->exists('foo.txt'));
        $this->assertSame(false, Storage::disk('public')->exists('foo.txt'));
    }

    public function test_disk_failed(): void
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\\Filesystem\\Storage.disks.nothing is blank or not define.");

        $this->assertInstanceOf(Filesystem::class, Storage::disk('nothing'));
    }

    public function test_disk_definition(): void
    {
        // The disk is the instantiation definition of the filesystem (the adapter definition is instantiated by Storage, and it is given to the filesystem)
        Config::runtime([Storage::class => ['disks' => ['memory' => [
            '@factory' => BuiltinFilesystem::class,
            'adapter'  => InMemoryFilesystemAdapter::class,
        ]]]]);
        $memory = Storage::disk('memory');
        $this->assertInstanceOf(BuiltinFilesystem::class, $memory);
        $this->assertInstanceOf(InMemoryFilesystemAdapter::class, $memory->adapter());
        $memory->put('foo.txt', 'foo');
        $this->assertSame('foo', $memory->get('foo.txt'));
        $this->assertSame($memory, Storage::disk('memory'));
    }

    public function test_disk_withoutFactory(): void
    {
        Config::runtime([Storage::class => ['disks' => ['no_factory' => ['adapter' => InMemoryFilesystemAdapter::class]]]]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid disk 'no_factory', the disk must be an instantiation definition array that has '@factory' of " . Filesystem::class . ".");
        Storage::disk('no_factory');
    }

    public function test_disk_invalidFactoryClass(): void
    {
        $instantiated = false;
        Config::runtime([Storage::class => ['disks' => ['invalid' => [
            '@factory' => \stdClass::class,
            'adapter'  => function () use (&$instantiated) {
                $instantiated = true;
                return new InMemoryFilesystemAdapter();
            },
        ]]]]);
        try {
            Storage::disk('invalid');
            $this->fail('Never execute.');
        } catch (LogicException $e) {
            $this->assertSame("Invalid disk 'invalid', the '@factory' of the disk must be an implementation class of " . Filesystem::class . " but stdClass given.", $e->getMessage());
        }
        $this->assertFalse($instantiated, 'The adapter must not be instantiated when the disk factory is invalid.');
    }

    public function test_disk_invalidFactoryResult(): void
    {
        Config::runtime([Storage::class => ['disks' => ['invalid' => [
            '@factory' => fn($adapter) => new \stdClass(),
            'adapter'  => InMemoryFilesystemAdapter::class,
        ]]]]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid disk 'invalid', the disk must be an instance of " . Filesystem::class . " but stdClass given.");
        Storage::disk('invalid');
    }

    public function test_disk_withoutAdapter(): void
    {
        Config::runtime([Storage::class => ['disks' => ['no_adapter' => ['@factory' => BuiltinFilesystem::class]]]]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid disk 'no_adapter', the 'adapter' of the disk is undefined.");
        Storage::disk('no_adapter');
    }

    public function test_clean(): void
    {
        Storage::private()->put('foo.txt', 'foo');
        Storage::public()->put('bar.txt', 'bar');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('bar.txt'));
        Storage::reset('private');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('bar.txt'));

        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        Storage::reset();
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('bar.txt'));
    }

    public function test_copy_failed(): void
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("Can not copy `private:foo.txt` to `public:foo.txt`, `public:foo.txt` already exists.");

        Storage::private()->put('foo.txt', 'foo');
        Storage::public()->put('foo.txt', 'foo');
        Storage::copy('private', 'foo.txt', 'public');
    }

    public function test_copy(): void
    {
        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::copy('private', 'foo.txt', 'public');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('foo.txt'));
        Storage::reset();

        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::copy('private', 'foo.txt', 'public', 'bar.txt');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('bar.txt'));
        $this->assertSame('foo', Storage::public()->get('bar.txt'));
        Storage::reset();

        Storage::private()->put('dir/foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        Storage::copy('private', 'dir/foo.txt', 'public');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/foo.txt'));
        Storage::reset();

        Storage::private()->put('dir/foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        Storage::copy('private', 'dir/foo.txt', 'public', 'dir/foo/bar.txt');
        $this->assertSame(true, Storage::private()->exists('dir/foo.txt'));
        $this->assertSame(false, Storage::public()->exists('dir/foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/foo/bar.txt'));
        Storage::reset();

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
        Storage::reset();

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
        Storage::reset();
    }

    public function test_move(): void
    {
        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::move('private', 'foo.txt', 'public');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('foo.txt'));
    }

    public function test_publish(): void
    {
        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::publish('foo.txt');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('foo.txt'));
        $this->assertSame('foo', Storage::public()->get('foo.txt'));
        Storage::reset();

        Storage::private()->put('foo.txt', 'foo');
        $this->assertSame(true, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        Storage::publish('foo.txt', 'dir/bar.txt');
        $this->assertSame(false, Storage::private()->exists('foo.txt'));
        $this->assertSame(false, Storage::public()->exists('foo.txt'));
        $this->assertSame(true, Storage::public()->exists('dir/bar.txt'));
        $this->assertSame('foo', Storage::public()->get('dir/bar.txt'));
        Storage::reset();
    }
}
