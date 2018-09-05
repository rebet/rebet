<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\IO\FileUtil;
use Rebet\IO\ZipArchiveException;

use org\bovigo\vfs\vfsStream;

class FileUtilTest extends RebetTestCase {

    private $root;

    public function setUp() {
        $this->root = vfsStream::setup();
        vfsStream::create([
                'public' => [
                    'css' => [
                        'normalize.css' => 'This is normalize.css',
                        'application.css' => 'This is application.css',
                    ],
                    'img' => [
                        // empty directory
                    ],
                    'js' => [
                        'application.js' => 'This is application.js',
                        'underscore' => [
                            'underscore.min.js' => 'This is underscore.min.js'
                        ]
                    ],
                    'index.html' => 'This is index.html',
                    'robot.txt' => '', // empty file
                ],
                'var' => [],
            ],
            $this->root
        );
        
    }

    public function test_removeDir() {
        $this->assertFileExists('vfs://root/public/css/normalize.css');
        $this->assertFileExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileExists('vfs://root/public/index.html');
        $this->assertFileExists('vfs://root/public/img');
        $this->assertFileExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');

        FileUtil::removeDir('vfs://root/public');

        $this->assertFileNotExists('vfs://root/public/css/normalize.css');
        $this->assertFileNotExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileNotExists('vfs://root/public/index.html');
        $this->assertFileNotExists('vfs://root/public/img');
        $this->assertFileNotExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');
    }

    public function test_zip() {
        if (DIRECTORY_SEPARATOR == '\\') {
            // vfsStream not supported ZipArchive::open() when DIRECTORY_SEPARATOR == '\\'
            try {
                FileUtil::zip('vfs://root/public','vfs://root/var/public.zip');
                $this->faile("vfsStream support ZipArchive::open() when DIRECTORY_SEPARATOR == '\\', so please update test code.");
            } catch (ZipArchiveException $e) {
                $this->assertSame(\ZipArchive::ER_READ, $e->getCode());
            }
        } else {
            try {
                FileUtil::zip('vfs://root/public','vfs://root/var/public.zip');
                $this->faile("vfsStream support ZipArchive::close(), so please update test code.");
            } catch(\ErrorException $e) {
                $this->assertSame(
                    'ZipArchive::close(): Failure to create temporary file: No such file or directory',
                    $e->getMessage()
                );
            }
        }
    }

    public function test_unzip() {
        if (DIRECTORY_SEPARATOR == '\\') {
            // vfsStream not supported ZipArchive::open() when DIRECTORY_SEPARATOR == '\\'
            try {
                FileUtil::unzip('vfs://root/var/public.zip','vfs://root/public');
                $this->faile("vfsStream support ZipArchive::open() when DIRECTORY_SEPARATOR == '\\', so please update test code.");
            } catch (ZipArchiveException $e) {
                $this->assertSame(\ZipArchive::ER_READ, $e->getCode());
            }
        } else {
            $this->markTestSkipped("vfsStream not support ZipArchive, yet.");
        }
    }
}