<?php
namespace Rebet\Tests\File;

use Rebet\File\Files;
use Rebet\Tests\RebetTestCase;

class FilesTest extends RebetTestCase
{
    protected $test_dir;

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

        $this->assertFileNotExists('vfs://root/public/css/normalize.css');
        $this->assertFileNotExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileNotExists('vfs://root/public/index.html');
        $this->assertFileNotExists('vfs://root/public/img');
        $this->assertFileNotExists('vfs://root/public');
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

        $this->assertFileNotExists('vfs://root/public/css/normalize.css');
        $this->assertFileNotExists('vfs://root/public/js/underscore/underscore.min.js');
        $this->assertFileNotExists('vfs://root/public/index.html');
        $this->assertFileNotExists('vfs://root/public/img');
        $this->assertFileExists('vfs://root/public');
        $this->assertFileExists('vfs://root/var');
        $this->assertFileExists('vfs://root');
    }

    public function test_zip()
    {
        $this->assertFileNotExists("{$this->test_dir}/parent.zip");
        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent.zip");

        // @todo inplements more tests
    }

    public function test_unzip()
    {
        $this->assertFileNotExists("{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent");

        Files::zip("{$this->test_dir}/parent", "{$this->test_dir}/parent.zip");

        $this->assertFileExists("{$this->test_dir}/parent.zip");
        $this->assertFileExists("{$this->test_dir}/parent");

        Files::removeDir("{$this->test_dir}/parent");

        $this->assertFileExists("{$this->test_dir}/parent.zip");
        $this->assertFileNotExists("{$this->test_dir}/parent");

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
}
