<?php
namespace Rebet\Tests\Http\Bag;

use Rebet\Foundation\App;
use Rebet\Http\Bag\FileBag;
use Rebet\Http\UploadedFile;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class FileBagTest extends RebetTestCase
{
    public function test_convert()
    {
        $file_info = [
            'tmp_name' => App::path('/resources/image/72x72.png'),
            'name'     => 'OriginalName_1.png',
            'type'     => 'image/png',
            'error'    => 0,
            'size'     => 454,
        ];
        $symfony_file = new SymfonyUploadedFile(App::path('/resources/image/72x72.png'), 'OriginalName.png');

        $bag = new FileBag(['file' => $file_info]);
        $this->assertInstanceOf(UploadedFile::class, $bag->get('file'));

        $bag = new FileBag(['file' => $symfony_file]);
        $this->assertInstanceOf(UploadedFile::class, $bag->get('file'));

        $bag = new FileBag(['file' => [$file_info, $symfony_file]]);
        foreach ($bag->get('file') as $upload_file) {
            $this->assertInstanceOf(UploadedFile::class, $upload_file);
        }

        $bag = new FileBag();
        $bag->set('file', $file_info);
        $this->assertInstanceOf(UploadedFile::class, $bag->get('file'));

        $bag = new FileBag();
        $bag->set('file', $symfony_file);
        $this->assertInstanceOf(UploadedFile::class, $bag->get('file'));

        $bag = new FileBag();
        $bag->set('file', [$file_info, $symfony_file]);
        foreach ($bag->get('file') as $upload_file) {
            $this->assertInstanceOf(UploadedFile::class, $upload_file);
        }
    }
}
