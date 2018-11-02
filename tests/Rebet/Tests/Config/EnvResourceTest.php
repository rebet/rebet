<?php
namespace Rebet\Tests\Config;

use org\bovigo\vfs\vfsStream;
use Rebet\Config\EnvResource;

use Rebet\Tests\RebetTestCase;

class EnvResourceTest extends RebetTestCase
{
    private $root;

    public function setUp()
    {
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
        'array' => [4],
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
                'int'    => 2,
                'string' => 'a',
                'array'  => [1 , 2 , 3, 1 , 2 , 3, 4],
                'map'    => [
                    'int'    => 1,
                    'string' => 'A',
                    'array'  => [1 , 2 , 3, 4],
                    'new'    => 'NEW',
                ],
                'new' => 'NEW',
            ],
            EnvResource::load('vfs://root/resource', 'test', 'unittest')
        );
        
        $this->assertSame(
            [
                'a' => [
                    'int'    => 2,
                    'string' => 'a',
                ],
                'b' => [
                    'string' => 'b',
                ]
            ],
            EnvResource::load('vfs://root/resource', 'test', 'unittest', 'ini')
        );
        
        $this->assertSame(
            [
                'int'    => 1,
                'string' => 'a',
                'array'  => [1 , 2 , 3],
                'map'    => [
                    'int'    => 1,
                    'string' => 'a',
                    'array'  => [1 , 2 , 3],
                ],
            ],
            EnvResource::load('vfs://root/resource', 'test', 'unittest', 'json')
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Resource test txt not found in vfs://root/resource.
     */
    public function test_load_notfound()
    {
        EnvResource::load('vfs://root/resource', 'test', 'unittest', 'txt');
        $this->fail("Never execute.");
    }
}
