<?php
namespace Rebet\Tests\Http;

use Rebet\Foundation\App;
use Rebet\Http\UploadedFile;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFileTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(UploadedFile::class, new UploadedFile(App::path('/resources/image/72x72.png'), '72x72.png'));
    }

    public function test_valueOf()
    {
        $file = App::path('/resources/image/72x72.png');
        $this->assertNull(UploadedFile::valueOf(null));
        $this->assertInstanceOf(UploadedFile::class, UploadedFile::valueOf(new UploadedFile($file, 'OriginalName')));
        $this->assertInstanceOf(UploadedFile::class, UploadedFile::valueOf(new SymfonyUploadedFile($file, 'OriginalName')));
        $this->assertInstanceOf(UploadedFile::class, UploadedFile::valueOf([
            'tmp_name' => $file,
            'name'     => 'OriginalName_1.png',
            'type'     => 'image/png',
            'error'    => 0,
            'size'     => 454,
        ]));
    }

    public function test_getWidth()
    {
        $this->assertSame(72, (new UploadedFile(App::path('/resources/image/72x72.png'), '72x72.png'))->getWidth());
        $this->assertSame(120, (new UploadedFile(App::path('/resources/image/120x60.png'), '120x60.png'))->getWidth());
        $this->assertSame(160, (new UploadedFile(App::path('/resources/image/160x240.png'), '160x240.png'))->getWidth());
        $this->assertSame(null, (new UploadedFile(App::path('/resources/.env.unittest'), '.env.unittest'))->getWidth());
    }

    public function test_getHeight()
    {
        $this->assertSame(72, (new UploadedFile(App::path('/resources/image/72x72.png'), '72x72.png'))->getHeight());
        $this->assertSame(60, (new UploadedFile(App::path('/resources/image/120x60.png'), '120x60.png'))->getHeight());
        $this->assertSame(240, (new UploadedFile(App::path('/resources/image/160x240.png'), '160x240.png'))->getHeight());
        $this->assertSame(null, (new UploadedFile(App::path('/resources/.env.unittest'), '.env.unittest'))->getHeight());
    }
}
