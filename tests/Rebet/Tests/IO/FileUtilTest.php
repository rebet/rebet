<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\IO\FileUtil;

use org\bovigo\vfs\vfsStream;

class FileUtilTest extends RebetTestCase {

    private $root;

    public function setUp() {
        $this->root = vfsStream::setup('root');
        
    }

    public function test_removeDir() {
    }
}
