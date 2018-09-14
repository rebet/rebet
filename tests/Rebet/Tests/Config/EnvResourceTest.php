<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\EnvResource;
use Rebet\Config\App;

use org\bovigo\vfs\vfsStream;

class EnvResourceTest extends RebetTestCase {

    private $root;

    public function setUp() {
        App::setEnv('unittest');
        $this->root = vfsStream::setup();
        vfsStream::create([
                'resource' => [
                    'test.php' => <<<EOS
<?php
return [
    'int' => 1,
    'string' => 'a',
    'array' => [1 ,2 , 3],
    'map' => [
        'int' => 1,
        'string' => 'a',
        'array' => [1 ,2 , 3],
    ],
];
EOS
                    ,
                    'test_unittest.php' => <<<EOS
<?php
return [
    'int' => 2,
    'array' => [1 ,2 , 3, 4],
    'map' => [
        'string' => 'A',
        'array' => [],
        'new' => 'NEW',
    ],
    'new' => 'NEW',
];
EOS
                    ,
                    'test.ini' => <<<EOS
[a]
int: 1
string: a
[b]
bool: on
string: b
EOS
                    ,
                    'test_unittest.ini' => <<<EOS
[a]
int: 2
new: NEW
[b]
string: B
[c]
new: C
EOS
                    ,
                ],
            ],
            $this->root
        );
    }

    public function test_load() {
        $this->assertSame(
            [
                'int' => 2,
                'string' => 'a',
                'array' => [1 ,2 , 3, 4],
                'map' => [
                    'int' => 1,
                    'string' => 'A',
                    'array' => [],
                    'new' => 'NEW',
                ],
                'new' => 'NEW',
            ],
            EnvResource::load('vfs://root/resource', 'test')
        );
    }

    // /**
    //  * @expectedException \LogicException
    //  * @expectedExceptionMessage Invalid path format: c:/invalid/../../path
    //  */
    // public function test_xxxxx_invalid() {
    // }

}