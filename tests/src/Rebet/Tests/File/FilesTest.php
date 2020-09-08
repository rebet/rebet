<?php
namespace Rebet\Tests\File;

use Rebet\Common\Strings;
use Rebet\File\Exception\ZipArchiveException;
use Rebet\File\Files;
use Rebet\Tests\RebetTestCase;

class FilesTest extends RebetTestCase
{
    protected $test_dir;

    protected function setUp() : void
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

        $this->test_dir = static::$unittest_cwd.'/FilesTest';
        Files::removeDir($this->test_dir);
        mkdir("{$this->test_dir}/parent", 0777, true);
        mkdir("{$this->test_dir}/parent/child", 0777, true);
        file_put_contents("{$this->test_dir}/parent/foo.txt", "foo");
        file_put_contents("{$this->test_dir}/parent/bar.ini", "bar");
        file_put_contents("{$this->test_dir}/parent/child/baz.log", "baz");
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

        $this->assertFileDoesNotExist('vfs://root/public/css/normalize.css');
        $this->assertFileDoesNotExist('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileDoesNotExist('vfs://root/public/index.html');
        $this->assertFileDoesNotExist('vfs://root/public/img');
        $this->assertFileDoesNotExist('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');
    }

    public function test_removeDir_onlyIncludeContents()
    {
        $this->assertFileExists('vfs://root/public/css/normalize.css');
        $this->assertFileExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileExists('vfs://root/public/index.html');
        $this->assertFileExists('vfs://root/public/img');
        $this->assertFileExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');

        Files::removeDir('vfs://root/public', false);

        $this->assertFileDoesNotExist('vfs://root/public/css/normalize.css');
        $this->assertFileDoesNotExist('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileDoesNotExist('vfs://root/public/index.html');
        $this->assertFileDoesNotExist('vfs://root/public/img');
        $this->assertFileExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');
    }

    public function test_zip()
    {
        $this->assertFileDoesNotExist("{$this->test_dir}/parent.zip");
        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent.zip");

        $this->assertFileDoesNotExist("{$this->test_dir}/archives");
        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/archives/1/parent.zip");
        $this->assertFileExists("{$this->test_dir}/archives/1");
        $this->assertFileExists("{$this->test_dir}/archives/1/parent.zip");
        Files::unzip("{$this->test_dir}/archives/1/parent.zip", "{$this->test_dir}/archives/1");
        $this->assertFileExists("{$this->test_dir}/archives/1/parent");

        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/archives/2/parent.zip", false);
        Files::unzip("{$this->test_dir}/archives/2/parent.zip", "{$this->test_dir}/archives/2");
        $this->assertFileDoesNotExist("{$this->test_dir}/archives/2/parent");
        $this->assertFileExists("{$this->test_dir}/archives/2/foo.txt");

        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/archives/3/parent.zip", false, function ($path) { return !Strings::endsWith($path, '.ini'); });
        Files::unzip("{$this->test_dir}/archives/3/parent.zip", "{$this->test_dir}/archives/3");
        $this->assertFileExists("{$this->test_dir}/archives/3/foo.txt");
        $this->assertFileDoesNotExist("{$this->test_dir}/archives/3/bar.ini");
        $this->assertFileExists("{$this->test_dir}/archives/3/child/baz.log");
    }

    public function test_unzip()
    {
        $this->assertFileDoesNotExist("{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent");

        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/parent.zip");

        $this->assertFileExists("{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent");

        Files::removeDir("{$this->test_dir}/parent");

        $this->assertFileExists("{$this->test_dir}/parent.zip");
        $this->assertFileDoesNotExist("{$this->test_dir}/parent");

        Files::unzip("{$this->test_dir}/parent.zip", $this->test_dir);

        $this->assertFileExists("{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent");
        $this->assertFileExists("{$this->test_dir}/parent/foo.txt");
        $this->assertSame("foo", file_get_contents("{$this->test_dir}/parent/foo.txt"));
        $this->assertFileExists("{$this->test_dir}/parent/bar.ini");
        $this->assertSame("bar", file_get_contents("{$this->test_dir}/parent/bar.ini"));
        $this->assertFileExists("{$this->test_dir}/parent/child");
        $this->assertFileExists("{$this->test_dir}/parent/child/baz.log");
        $this->assertSame("baz", file_get_contents("{$this->test_dir}/parent/child/baz.log"));
    }

    public function dataZipErrorChecks() : array
    {
        return [
            ['ZipArchive error.', false],
            [null, \ZipArchive::ER_OK],
            ["ZipArchive error. (Multi-disk zip archives not supported)" , \ZipArchive::ER_MULTIDISK],
            ["ZipArchive error. (Renaming temporary file failed)" , \ZipArchive::ER_RENAME],
            ["ZipArchive error. (Closing zip archive failed)" , \ZipArchive::ER_CLOSE],
            ["ZipArchive error. (Seek error)" , \ZipArchive::ER_SEEK],
            ["ZipArchive error. (Read error)" , \ZipArchive::ER_READ],
            ["ZipArchive error. (Write error)" , \ZipArchive::ER_WRITE],
            ["ZipArchive error. (CRC error)" , \ZipArchive::ER_CRC],
            ["ZipArchive error. (Containing zip archive was closed)" , \ZipArchive::ER_ZIPCLOSED],
            ["ZipArchive error. (No such file)" , \ZipArchive::ER_NOENT],
            ["ZipArchive error. (File already exists)" , \ZipArchive::ER_EXISTS],
            ["ZipArchive error. (Can't open file)" , \ZipArchive::ER_OPEN],
            ["ZipArchive error. (Failure to create temporary file)" , \ZipArchive::ER_TMPOPEN],
            ["ZipArchive error. (Zlib error)" , \ZipArchive::ER_ZLIB],
            ["ZipArchive error. (Malloc failure)" , \ZipArchive::ER_MEMORY],
            ["ZipArchive error. (Entry has been changed)" , \ZipArchive::ER_CHANGED],
            ["ZipArchive error. (Compression method not supported)" , \ZipArchive::ER_COMPNOTSUPP],
            ["ZipArchive error. (Premature EOF)" , \ZipArchive::ER_EOF],
            ["ZipArchive error. (Invalid argument)" , \ZipArchive::ER_INVAL],
            ["ZipArchive error. (Not a zip archive)" , \ZipArchive::ER_NOZIP],
            ["ZipArchive error. (Internal error)" , \ZipArchive::ER_INTERNAL],
            ["ZipArchive error. (Zip archive inconsistent)" , \ZipArchive::ER_INCONS],
            ["ZipArchive error. (Can't remove file)" , \ZipArchive::ER_REMOVE],
            ["ZipArchive error. (Entry has been delete)" , \ZipArchive::ER_DELETED],
            ["ZipArchive error. (Unknown reason)" , 999],
        ];
    }

    /**
     * @dataProvider dataZipErrorChecks
     */
    public function test_zipErrorCheck($expect, $code)
    {
        try {
            $this->invoke(Files::class, 'zipErrorCheck', [$code, 'ZipArchive error.']);
            if ($code === \ZipArchive::ER_OK) {
                $this->assertTrue(true);
            } else {
                $this->fail("Must not execute.");
            }
        } catch (ZipArchiveException $e) {
            $this->assertSame($expect, $e->getMessage());
        }
    }
}
