<?php
namespace Rebet\Tests\Filesystem;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\CacheInterface;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use Psr\Http\Message\StreamInterface;
use Rebet\Application\App;
use Rebet\Filesystem\BuiltinFilesystem;
use Rebet\Filesystem\Exception\FileNotFoundException;
use Rebet\Filesystem\Exception\FilesystemException;
use Rebet\Filesystem\Filesystem;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;

class BuiltinFilesystemTest extends RebetTestCase
{
    private $root;
    /** @var Filesystem */
    private $filesystem;

    protected function setUp() : void
    {
        parent::setUp();
        $this->root       = App::structure()->resources('/adhoc/Filesystem/BuiltinFilesystem');
        $this->filesystem = new BuiltinFilesystem(new Local($this->root));
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        $this->filesystem->clean();
    }

    public function test___construct()
    {
        $filesystem = new BuiltinFilesystem(new Local($this->root));
        $this->assertInstanceOf(BuiltinFilesystem::class, $filesystem);
    }

    public function test_driver()
    {
        $this->assertInstanceOf(FlysystemFilesystem::class, $this->filesystem->driver());
    }

    public function test_adapter()
    {
        $this->assertInstanceOf(Local::class, $this->filesystem->adapter());
    }

    public function test_exists()
    {
        foreach (['hello.txt', 'dir/hello.txt'] as $path) {
            $this->assertSame(false, $this->filesystem->exists($path));
            $this->assertSame($path, $this->filesystem->put($path, 'Hello'));
            $this->assertSame(true, $this->filesystem->exists($path));
        }
    }

    public function test_isFile()
    {
        $this->filesystem->put('dir/foo.txt', 'foo');
        $this->assertSame(false, $this->filesystem->isFile('dir'));
        $this->assertSame(true, $this->filesystem->isFile('dir/foo.txt'));
    }

    public function test_isDirectory()
    {
        $this->filesystem->put('dir/foo.txt', 'foo');
        $this->assertSame(true, $this->filesystem->isDirectory('dir'));
        $this->assertSame(false, $this->filesystem->isDirectory('dir/foo.txt'));
    }

    public function test_path()
    {
        $this->assertSame(App::structure()->resources('/adhoc/Filesystem/BuiltinFilesystem'), $this->filesystem->path());
        $this->assertSame(App::structure()->resources('/adhoc/Filesystem/BuiltinFilesystem/hello.txt'), $this->filesystem->path('hello.txt'));
    }

    public function test_get()
    {
        $path = 'hello.txt';
        $body = 'Hello';
        $this->filesystem->put($path, $body);
        $this->assertSame($body, $this->filesystem->get($path));
    }

    public function test_get_fileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage("File not found at path: not_found.txt");

        $this->filesystem->get('not_found.txt');
    }

    public function test_put()
    {
        foreach (['hello.txt', 'dir/hello.txt'] as $path) {
            $contents = "Hello {$path}";
            $this->assertSame(false, $this->filesystem->exists($path));
            $this->assertSame($path, $this->filesystem->put($path, $contents));
            $this->assertSame(true, $this->filesystem->exists($path));
            $this->assertSame($contents, $this->filesystem->get($path));

            $contents = "Update {$path}";
            $this->assertSame($path, $this->filesystem->put($path, $contents));
            $this->assertSame($contents, $this->filesystem->get($path));
        }

        $path = 'env_1.txt';
        $file = new \SplFileInfo(App::structure()->env('/.env'));
        $this->assertSame($path, $this->filesystem->put($path, $file));
        $this->assertStringStartsWith("APP_ENV=unittest\n", $this->filesystem->get($path));

        $path = 'env_1{.ext}';
        $file = new \SplFileInfo(App::structure()->env('/.env'));
        $this->assertSame('env_1.env', $this->filesystem->put($path, $file));
        $this->assertStringStartsWith("APP_ENV=unittest\n", $this->filesystem->get('env_1.env'));

        $path = 'env_2.txt';
        $file = fopen(App::structure()->env('/.env'), 'r');
        $this->assertSame($path, $this->filesystem->put($path, $file));
        $this->assertStringStartsWith("APP_ENV=unittest\n", $this->filesystem->get($path));
        fclose($file);

        $path   = 'env_3.txt';
        $file   = fopen(App::structure()->env('/.env'), 'r');
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('detach')->willReturn($file);
        $this->assertSame($path, $this->filesystem->put($path, $stream));
        $this->assertStringStartsWith("APP_ENV=unittest\n", $this->filesystem->get($path));
        fclose($file);
    }

    public function test_putFile()
    {
        $path     = 'env.txt';
        $contents = App::structure()->env('/.env');
        $this->assertSame($path, $this->filesystem->put($path, $contents));
        $this->assertSame($contents, $this->filesystem->get($path));

        $this->assertSame($path, $this->filesystem->putFile($path, $contents));
        $this->assertStringStartsWith("APP_ENV=unittest\n", $this->filesystem->get($path));
    }

    public function test_getAndSetVisibility()
    {
        $path     = 'hello.txt';
        $contents = 'Hello';
        $this->filesystem->put($path, $contents);

        if ($this->isWindows()) {
            // Windows can not set visibility to 'private'.
            $this->assertSame('public', $this->filesystem->getVisibility($path));
            $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->setVisibility($path, 'private'));
            $this->assertSame('public', $this->filesystem->getVisibility($path));
        } else {
            $this->assertSame('public', $this->filesystem->getVisibility($path));
            $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->setVisibility($path, 'private'));
            $this->assertSame('private', $this->filesystem->getVisibility($path));
            $this->filesystem->delete($path);

            $this->filesystem->put($path, $contents, 'private');
            $this->assertSame('private', $this->filesystem->getVisibility($path));
            $this->filesystem->delete($path);

            $this->filesystem->put($path, $contents, ['visibility' => 'private']);
            $this->assertSame('private', $this->filesystem->getVisibility($path));
            $this->filesystem->delete($path);
        }
    }

    public function test_prepend()
    {
        $path = 'hello.txt';
        $this->assertSame(false, $this->filesystem->exists($path));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->prepend($path, 'a'));
        $this->assertSame(true, $this->filesystem->exists($path));
        $this->assertSame("a", $this->filesystem->get($path));
        $this->filesystem->prepend($path, 'b');
        $this->assertSame("b\na", $this->filesystem->get($path));
        $this->filesystem->prepend($path, 'c');
        $this->assertSame("c\nb\na", $this->filesystem->get($path));
    }

    public function test_append()
    {
        $path = 'hello.txt';
        $this->assertSame(false, $this->filesystem->exists($path));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->append($path, 'a'));
        $this->assertSame(true, $this->filesystem->exists($path));
        $this->assertSame("a", $this->filesystem->get($path));
        $this->filesystem->append($path, 'b');
        $this->assertSame("a\nb", $this->filesystem->get($path));
        $this->filesystem->append($path, 'c');
        $this->assertSame("a\nb\nc", $this->filesystem->get($path));
    }

    public function test_delete()
    {
        foreach ([
            'a.txt',
            'b.txt',
            'c.csv',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'path/1/1.txt',
            'path/2/2.log',
            'path/3/3.ini',
        ] as $path) {
            $path = $this->filesystem->put($path, "Hello {$path}");
            $this->assertSame(true, $this->filesystem->exists($path));
        }

        $path = 'nothing.txt';
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->delete($path));
        $this->assertSame(false, $this->filesystem->exists($path));

        $paths = ['c.csv'];
        foreach ($paths as $path) {
            $this->assertSame(true, $this->filesystem->exists($path));
        }
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->delete(...$paths));
        foreach ($paths as $path) {
            $this->assertSame(false, $this->filesystem->exists($path));
        }

        $paths = ['a.txt', 'b.txt'];
        foreach ($paths as $path) {
            $this->assertSame(true, $this->filesystem->exists($path));
        }
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->delete(...$paths));
        foreach ($paths as $path) {
            $this->assertSame(false, $this->filesystem->exists($path));
        }

        $paths = ['dir/1.txt'];
        foreach ($paths as $path) {
            $this->assertSame(true, $this->filesystem->exists($path));
        }
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->delete(...$paths));
        foreach ($paths as $path) {
            $this->assertSame(false, $this->filesystem->exists($path));
        }

        $paths = ['dir'];
        foreach ($paths as $path) {
            $this->assertSame(true, $this->filesystem->exists($path));
        }
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->delete(...$paths));
        foreach ($paths as $path) {
            $this->assertSame(false, $this->filesystem->exists($path));
        }
        $this->assertSame(false, $this->filesystem->exists('dir/2.txt'));
        $this->assertSame(false, $this->filesystem->exists('dir/subdir/3.txt'));

        $paths = ['path/1', 'path/3'];
        foreach ($paths as $path) {
            $this->assertSame(true, $this->filesystem->exists($path));
        }
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->delete(...$paths));
        foreach ($paths as $path) {
            $this->assertSame(false, $this->filesystem->exists($path));
        }
        $this->assertSame(true, $this->filesystem->exists('path/2/2.log'));
    }

    public function test_clean()
    {
        foreach ([
            'a.txt',
            'b.txt',
            'c.csv',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'path/1/1.txt',
            'path/2/2.log',
            'path/3/3.ini',
        ] as $path) {
            $path = $this->filesystem->put($path, "Hello {$path}");
            $this->assertSame(true, $this->filesystem->exists($path));
        }

        $this->assertSame(true, $this->filesystem->exists('dir'));
        $this->assertSame(true, $this->filesystem->exists('dir/2.txt'));
        $this->assertSame(true, $this->filesystem->exists('dir/subdir'));
        $this->assertSame(['dir/1.txt', 'dir/2.txt', 'dir/subdir'], $this->filesystem->ls('dir'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->clean('dir'));
        $this->assertSame(true, $this->filesystem->exists('dir'));
        $this->assertSame(false, $this->filesystem->exists('dir/2.txt'));
        $this->assertSame(false, $this->filesystem->exists('dir/subdir'));
        $this->assertSame([], $this->filesystem->ls('dir'));

        $this->assertSame(true, $this->filesystem->exists('dir'));
        $this->assertSame(true, $this->filesystem->exists('a.txt'));
        $this->assertSame(true, $this->filesystem->exists('path/1/1.txt'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->clean());
        $this->assertSame(false, $this->filesystem->exists('dir'));
        $this->assertSame(false, $this->filesystem->exists('a.txt'));
        $this->assertSame(false, $this->filesystem->exists('path/1/1.txt'));
        $this->assertSame([], $this->filesystem->ls());
    }

    public function test_copy()
    {
        $this->filesystem->put('from.txt', "Hello a");
        $this->assertSame(false, $this->filesystem->exists('to.txt'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->copy('from.txt', 'to.txt'));
        $this->assertSame(true, $this->filesystem->exists('to.txt'));
        $this->assertSame("Hello a", $this->filesystem->get('to.txt'));
    }

    public function test_copy_replace()
    {
        $this->filesystem->put('from.txt', "1");
        $this->filesystem->put('to.txt', "2");
        $this->assertSame("2", $this->filesystem->get('to.txt'));
        $this->filesystem->copy('from.txt', 'to.txt', true);
        $this->assertSame("1", $this->filesystem->get('to.txt'));
    }

    public function test_copy_replace_faile()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("File already exists at path: to.txt");

        $this->filesystem->put('from.txt', "1");
        $this->filesystem->put('to.txt', "2");
        $this->filesystem->copy('from.txt', 'to.txt');
    }

    public function test_copy_dir()
    {
        $this->filesystem->put('dir_from/1.txt', "1");
        $this->filesystem->put('dir_from/2.txt', "2");
        $this->filesystem->put('dir_from/sub/3.txt', "3");
        $this->filesystem->mkdir('dir_from/sub/sub');
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->copy('dir_from', 'dir_to'));
        $this->assertSame("1", $this->filesystem->get('dir_to/1.txt'));
        $this->assertSame("2", $this->filesystem->get('dir_to/2.txt'));
        $this->assertSame("3", $this->filesystem->get('dir_to/sub/3.txt'));
        $this->assertSame(true, $this->filesystem->exists('dir_to/sub/sub'));

        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->copy('/dir_to/sub', '/foo/bar/baz'));
        $this->assertSame("3", $this->filesystem->get('foo/bar/baz/3.txt'));
        $this->assertSame(true, $this->filesystem->exists('foo/bar/baz/sub'));
    }

    public function test_copy_dir_replace()
    {
        $this->filesystem->put('dir_from/1.txt', "1");
        $this->filesystem->put('dir_to/2.txt', "2");
        $this->assertSame(true, $this->filesystem->exists('dir_to/2.txt'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->copy('dir_from', 'dir_to', true));
        $this->assertSame("1", $this->filesystem->get('dir_to/1.txt'));
        $this->assertSame(false, $this->filesystem->exists('dir_to/2.txt'));
    }

    public function test_copy_dir_replace_failed()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("Can not copy from `dir_from` to `dir_to`. `dir_to` directory already exists.");

        $this->filesystem->put('dir_from/1.txt', "1");
        $this->filesystem->put('dir_to/2.txt', "2");
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->copy('dir_from', 'dir_to'));
    }

    public function test_move()
    {
        $this->filesystem->put('from.txt', "1");
        $this->assertSame(false, $this->filesystem->exists('to.txt'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->move('from.txt', 'to.txt'));
        $this->assertSame(false, $this->filesystem->exists('from.txt'));
        $this->assertSame(true, $this->filesystem->exists('to.txt'));
        $this->assertSame("1", $this->filesystem->get('to.txt'));

        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->move('to.txt', 'dir/to.txt'));
        $this->assertSame(false, $this->filesystem->exists('to.txt'));
        $this->assertSame(true, $this->filesystem->exists('dir/to.txt'));
    }

    public function test_move_replace()
    {
        $this->filesystem->put('from.txt', "1");
        $this->filesystem->put('to.txt', "2");
        $this->assertSame("2", $this->filesystem->get('to.txt'));
        $this->filesystem->move('from.txt', 'to.txt', true);
        $this->assertSame("1", $this->filesystem->get('to.txt'));
    }

    public function test_move_replace_faile()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("File already exists at path: to.txt");

        $this->filesystem->put('from.txt', "1");
        $this->filesystem->put('to.txt', "2");
        $this->filesystem->move('from.txt', 'to.txt');
    }

    public function test_move_dir()
    {
        $this->filesystem->put('dir_from/1.txt', "1");
        $this->filesystem->put('dir_from/2.txt', "2");
        $this->filesystem->put('dir_from/sub/3.txt', "3");
        $this->filesystem->mkdir('dir_from/sub/sub');
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->move('dir_from', 'dir_to'));
        $this->assertSame(false, $this->filesystem->exists('dir_from'));
        $this->assertSame("1", $this->filesystem->get('dir_to/1.txt'));
        $this->assertSame("2", $this->filesystem->get('dir_to/2.txt'));
        $this->assertSame("3", $this->filesystem->get('dir_to/sub/3.txt'));
        $this->assertSame(true, $this->filesystem->exists('dir_to/sub/sub'));

        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->move('/dir_to/sub', '/foo/bar/baz'));
        $this->assertSame(false, $this->filesystem->exists('dir_to/sub'));
        $this->assertSame("3", $this->filesystem->get('foo/bar/baz/3.txt'));
        $this->assertSame(true, $this->filesystem->exists('foo/bar/baz/sub'));
    }

    public function test_move_dir_replace()
    {
        $this->filesystem->put('dir_from/1.txt', "1");
        $this->filesystem->put('dir_to/2.txt', "2");
        $this->assertSame(true, $this->filesystem->exists('dir_to/2.txt'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->move('dir_from', 'dir_to', true));
        $this->assertSame(false, $this->filesystem->exists('dir_from'));
        $this->assertSame("1", $this->filesystem->get('dir_to/1.txt'));
        $this->assertSame(false, $this->filesystem->exists('dir_to/2.txt'));
    }

    public function test_move_dir_replace_failed()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("File already exists at path: dir_to");

        $this->filesystem->put('dir_from/1.txt', "1");
        $this->filesystem->put('dir_to/2.txt', "2");
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->move('dir_from', 'dir_to'));
    }

    public function test_size()
    {
        $path = 'size.test';
        $this->filesystem->put($path, "1");
        $this->assertSame(1, $this->filesystem->size($path));

        $this->filesystem->put($path, "1234567890");
        $this->assertSame(10, $this->filesystem->size($path));

        $this->filesystem->put('72x72.png', fopen(App::structure()->public('/assets/img/72x72.png'), 'r'));
        $this->assertSame(454, $this->filesystem->size('72x72.png'));
    }

    public function test_metadata()
    {
        $path = '1.txt';
        $this->filesystem->put($path, "1");
        $metadata = $this->filesystem->metadata($path);
        $this->assertSame('file', $metadata['type']);
        $this->assertSame($path, $metadata['path']);
        $this->assertSame($this->filesystem->size($path), $metadata['size']);
        $this->assertNotNull($metadata['timestamp'] ?? null);

        $path = 'dir';
        $this->filesystem->mkdir($path);
        $metadata = $this->filesystem->metadata($path);
        $this->assertSame('dir', $metadata['type']);
        $this->assertSame($path, $metadata['path']);
        $this->assertNull($metadata['size'] ?? null);
        $this->assertNotNull($metadata['timestamp'] ?? null);

        $path = 'dir/2.csv';
        $this->filesystem->put($path, "1,2,3");
        $metadata = $this->filesystem->metadata($path);
        $this->assertSame('file', $metadata['type']);
        $this->assertSame($path, $metadata['path']);
        $this->assertSame($this->filesystem->size($path), $metadata['size']);
        $this->assertNotNull($metadata['timestamp'] ?? null);

        $path = '72x72.png';
        $this->filesystem->put($path, fopen(App::structure()->public('/assets/img/72x72.png'), 'r'));
        $metadata = $this->filesystem->metadata($path);
        $this->assertSame('file', $metadata['type']);
        $this->assertSame($path, $metadata['path']);
        $this->assertSame($this->filesystem->size($path), $metadata['size']);
        $this->assertNotNull($metadata['timestamp'] ?? null);
    }

    public function test_mimeType()
    {
        $path = '1.txt';
        $this->filesystem->put($path, "1");
        $mime_type = $this->filesystem->mimeType($path);
        $this->assertSame('text/plain', $mime_type);

        $path = 'dir';
        $this->filesystem->mkdir($path);
        $mime_type = $this->filesystem->mimeType($path);
        $this->assertSame('directory', $mime_type);

        $path = 'dir/2.csv';
        $this->filesystem->put($path, "1,2,3");
        $mime_type = $this->filesystem->mimeType($path);
        $this->assertSame('text/csv', $mime_type);

        $path = '72x72.png';
        $this->filesystem->put($path, fopen(App::structure()->public('/assets/img/72x72.png'), 'r'));
        $mime_type = $this->filesystem->mimeType($path);
        $this->assertSame('image/png', $mime_type);
    }

    public function test_lastModified()
    {
        $path  = '1.txt';
        $start = DateTime::now()->setMilliMicro(0);
        $this->filesystem->put($path, "1");
        $end           = DateTime::now()->setMilliMicro(0);
        $last_modified = $this->filesystem->lastModified($path);
        $this->assertNotNull($last_modified);
        $this->assertTrue($start <= $last_modified);
        $this->assertTrue($last_modified <= $end);
    }

    public function test_url_private()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage("/foo/bar.txt is not public.");

        $local = $this->createMock(Local::class);
        $local->method('getVisibility')->willReturn(['visibility' => 'private']);
        $filesystem = new BuiltinFilesystem($local, ['disable_asserts' => true]);
        $filesystem->url('/foo/bar.txt');
    }

    public function test_url()
    {
        $public = new BuiltinFilesystem(new Local($this->root), [
            'visibility' => 'public',
            'url'        => '/storage/public'
        ]);
        $public->put('foo/bar.txt', 'foo bar');
        $this->assertSame('/storage/public/foo/bar.txt', $public->url('foo/bar.txt'));
        $this->assertSame('/storage/public/foo/bar.txt', $public->url('/foo/bar.txt'));

        $public = new BuiltinFilesystem(new Local($this->root), [
            'visibility' => 'public',
        ]);
        $this->assertSame('/foo/bar.txt', $public->url('foo/bar.txt'));
        $this->assertSame('/foo/bar.txt', $public->url('/foo/bar.txt'));
        $public->clean();
    }

    public function test_readStream()
    {
        $this->filesystem->put('foo.txt', 'foo');
        $file = $this->filesystem->readStream('foo.txt');
        $this->assertIsResource($file);
        $this->assertSame('foo', stream_get_contents($file));
    }

    public function test_ls()
    {
        foreach ([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'path/1/1.txt',
            'path/2/2.log',
            'path/3/3.ini',
            'foo.txt/bar.txt',
        ] as $path) {
            $this->filesystem->put($path, "Hello {$path}");
            $this->assertSame(true, $this->filesystem->exists($path));
        }

        $this->assertSame([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
            'dir',
            'foo.txt',
            'path',
        ], $this->filesystem->ls());

        $this->assertSame([
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir',
        ], $this->filesystem->ls('dir'));

        $this->assertSame([
            'a.txt',
            'b.txt',
            'foo.txt',
        ], $this->filesystem->ls('/', '*.txt'));

        $this->assertSame([
            'a.txt',
            'b.txt',
        ], $this->filesystem->ls('/', '*.txt', 'file'));

        $this->assertSame([
        ], $this->filesystem->ls('/', 'dir/*.txt', null));

        $this->assertSame([
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
        ], $this->filesystem->ls('/', 'dir/*.txt', null, true));

        $this->assertSame([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
        ], $this->filesystem->ls('/', '*', 'file'));

        $this->assertSame([
            'dir',
            'foo.txt',
            'path',
        ], $this->filesystem->ls('/', '*', 'dir'));

        $this->assertSame([
            'dir',
            'dir/subdir',
            'foo.txt',
            'path',
            'path/1',
            'path/2',
            'path/3',
        ], $this->filesystem->ls('/', '*', 'dir', true));

        $this->assertSame([
            'dir/1.txt',
            'dir/2.txt',
        ], $this->filesystem->ls('/', '/^dir\/[^\/]*.txt/', null, true, 'regex'));

        $this->assertSame([
            'a.txt',
            'b.txt',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'foo.txt',
            'foo.txt/bar.txt',
            'path/1/1.txt',
        ], $this->filesystem->ls('/', '*.txt', null, true));
    }

    public function test_files()
    {
        foreach ([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'path/1/1.txt',
            'path/2/2.log',
            'path/3/3.ini',
            'foo.txt/bar.txt',
        ] as $path) {
            $this->filesystem->put($path, "Hello {$path}");
            $this->assertSame(true, $this->filesystem->exists($path));
        }

        $this->assertSame([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
        ], $this->filesystem->files());

        $this->assertSame([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'foo.txt/bar.txt',
            'path/1/1.txt',
            'path/2/2.log',
            'path/3/3.ini',
        ], $this->filesystem->files('/', '*', true));
    }

    public function test_directories()
    {
        foreach ([
            'a.txt',
            'b.txt',
            'c.csv',
            'd.ini',
            'dir/1.txt',
            'dir/2.txt',
            'dir/subdir/3.txt',
            'path/1/1.txt',
            'path/2/2.log',
            'path/3/3.ini',
            'foo.txt/bar.txt',
        ] as $path) {
            $this->filesystem->put($path, "Hello {$path}");
            $this->assertSame(true, $this->filesystem->exists($path));
        }

        $this->assertSame([
            'dir',
            'foo.txt',
            'path',
        ], $this->filesystem->directories());

        $this->assertSame([
            'dir',
            'dir/subdir',
            'foo.txt',
            'path',
            'path/1',
            'path/2',
            'path/3',
        ], $this->filesystem->directories('/', '*', true));
    }

    public function test_mkdir()
    {
        $this->assertSame(false, $this->filesystem->exists('dir'));
        $this->assertInstanceOf(BuiltinFilesystem::class, $this->filesystem->mkdir('dir'));
        $this->assertSame(true, $this->filesystem->exists('dir'));
    }

    public function test_flush()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('flush');

        $adapter = $this->createMock(CachedAdapter::class);
        $adapter->method('getCache')->willReturn($cache);

        $filesystem = new BuiltinFilesystem($adapter);
        $filesystem->flush();
    }
}
