<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\EnvResource;
use Rebet\Config\App;

use org\bovigo\vfs\vfsStream;

class EnvResourceTest extends RebetTestCase
{
    private $root;

    public function setUp()
    {
        App::setEnv('unittest');
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
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
                    'test_unittest.ini' => <<<EOS
[a]
int = 2
string = a
[b]
string = b
EOS
                    ,
                    'test.json' => <<<EOS
{
    "int": 1,
    "string": "a",
    "array": [1 ,2 , 3],
    "map": {
        "int": 1,
        "string": "a",
        "array": [1 ,2 , 3]
    }
}
EOS
                    ,
                ],
            ],
            $this->root
        );
    }

    public function test_load()
    {
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
        
        $this->assertSame(
            [
                'a' => [
                    'int' => 2,
                    'string' => 'a',
                ],
                'b' => [
                    'string' => 'b',
                ]
            ],
            EnvResource::load('vfs://root/resource', 'test', 'ini')
        );
        
        $this->assertSame(
            [
                'int' => 1,
                'string' => 'a',
                'array' => [1 ,2 , 3],
                'map' => [
                    'int' => 1,
                    'string' => 'a',
                    'array' => [1 ,2 , 3],
                ],
            ],
            EnvResource::load('vfs://root/resource', 'test', 'json')
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Resource test txt not found in vfs://root/resource.
     */
    public function test_load_notfound()
    {
        EnvResource::load('vfs://root/resource', 'test', 'txt');
        $this->fail("Never execute.");
    }
}
