<?php
namespace Rebet\Tests\File;

use Rebet\File\Exception\ZipArchiveException;
use Rebet\File\Files;
use Rebet\Tests\RebetTestCase;

class FilesTest extends RebetTestCase
{
    public function setUp()
    {
        $this->vfs([
            'public' => [
                'css' => [
                    'normalize.css'   => 'This is normalize.css',
                    'application.css' => 'This is application.css',
                ],
                'img' => [
                    // empty directory
                ],
                'js' => [
                    'application.js' => 'This is application.js',
                    'underscore'     => [
                        'underscore.min.js' => 'This is underscore.min.js'
                    ]
                ],
                'index.html' => 'This is index.html',
                'robot.txt'  => '', // empty file
            ],
            'var' => [],
        ]);
    }

    public function test_removeDir()
    {
        $this->assertFileExists('vfs://root/public/css/normalize.css');
        $this->assertFileExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileExists('vfs://root/public/index.html');
        $this->assertFileExists('vfs://root/public/img');
        $this->assertFileExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');

        Files::removeDir('vfs://root/public');

        $this->assertFileNotExists('vfs://root/public/css/normalize.css');
        $this->assertFileNotExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileNotExists('vfs://root/public/index.html');
        $this->assertFileNotExists('vfs://root/public/img');
        $this->assertFileNotExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');
    }

    public function test_zip()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            // vfsStream not supported ZipArchive::open() when DIRECTORY_SEPARATOR == '\\'
            try {
                Files::zip('vfs://root/public', 'vfs://root/var/public.zip');
                $this->fail("vfsStream support ZipArchive::open() when DIRECTORY_SEPARATOR == '\\', so please update test code.");
            } catch (ZipArchiveException $e) {
                $this->assertSame(\ZipArchive::ER_READ, $e->getCode());
            }
        } else {
            try {
                Files::zip('vfs://root/public', 'vfs://root/var/public.zip');
                $zip = file_get_contents('vfs://root/var/public.zip');
                $this->assertNotFalse($zip);
            } catch (\ErrorException $e) {
                $this->assertSame(
                    'ZipArchive::close(): Failure to create temporary file: No such file or directory',
                    $e->getMessage()
                );
            }
        }
    }

    public function test_unzip()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            // vfsStream not supported ZipArchive::open() when DIRECTORY_SEPARATOR == '\\'
            try {
                Files::unzip('vfs://root/var/public.zip', 'vfs://root/public');
                $this->fail("vfsStream support ZipArchive::open() when DIRECTORY_SEPARATOR == '\\', so please update test code.");
            } catch (ZipArchiveException $e) {
                $this->assertSame(\ZipArchive::ER_READ, $e->getCode());
            }
        } else {
            $this->markTestSkipped("vfsStream not support ZipArchive, yet.");
        }
    }
}
