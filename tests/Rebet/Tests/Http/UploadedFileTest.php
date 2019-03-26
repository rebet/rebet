<?php
namespace Rebet\Tests\Http;

use Rebet\Foundation\App;
use Rebet\Http\UploadedFile;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFileTest extends RebetTestCase
{
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
}
