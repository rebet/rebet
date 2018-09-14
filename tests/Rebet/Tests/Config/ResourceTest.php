<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Resource;

use org\bovigo\vfs\vfsStream;

class ResourceTest extends RebetTestCase {

    private $root;

    public function setUp() {
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
                    'test.ini' => <<<EOS
[a]
int = 1
string = a
[b]
bool = on
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
                    'test.txt' => <<<EOS
1st
2nd
3rd
4th
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
                'int' => 1,
                'string' => 'a',
                'array' => [1 ,2 , 3],
                'map' => [
                    'int' => 1,
                    'string' => 'a',
                    'array' => [1 ,2 , 3],
                ],
            ],
            Resource::load('php', 'vfs://root/resource/test.php')
        );
        
        $this->assertSame(
            [
                'a' => [
                    'int' => 1,
                    'string' => 'a',
                ],
                'b' => [
                    'bool' => true,
                    'string' => 'b',
                ],
            ],
            Resource::load('ini', 'vfs://root/resource/test.ini')
        );
        
        $this->assertSame(
            [
                'int' => 1,
                'string' => 'b',
                'bool' => true,
            ],
            Resource::load('ini', 'vfs://root/resource/test.ini', ['process_sections' => false])
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
            Resource::load('json', 'vfs://root/resource/test.json')
        );
        
        $this->assertSame(
            [
                '1st',
                '2nd',
                '3rd',
                '4th',
            ],
            Resource::load('txt', 'vfs://root/resource/test.txt')
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported file type [yaml]. Please set loader to Rebet\Config\Resource class.
     */
    public function test_load_unsuported() {
        Resource::load('yaml', 'vfs://root/resource/test.yaml');
    }

    public function test_load_notfound() {
        $this->assertNull(Resource::load('php', 'vfs://root/resource/notfound.php'));
    }
}