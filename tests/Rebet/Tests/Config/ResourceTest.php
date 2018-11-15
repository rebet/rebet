<?php
namespace Rebet\Tests\Config;

use Rebet\Config\Resource;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;

class ResourceTest extends RebetTestCase
{
    private $resources;

    public function setUp()
    {
        parent::setUp();
        $this->resources = App::path('/resources/Config/Resource');
    }

    public function test_load()
    {
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
            Resource::load('php', $this->resources.'/test.php')
        );
        
        $this->assertSame(
            [
                'a' => [
                    'int'    => 1,
                    'string' => 'a',
                ],
                'b' => [
                    'bool'   => true,
                    'string' => 'b',
                ],
            ],
            Resource::load('ini', $this->resources.'/test.ini')
        );
        
        $this->assertSame(
            [
                'int'    => 1,
                'string' => 'b',
                'bool'   => true,
            ],
            Resource::load('ini', $this->resources.'/test.ini', ['process_sections' => false])
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
            Resource::load('json', $this->resources.'/test.json')
        );
        
        $this->assertSame(
            [
                '1st',
                '2nd',
                '3rd',
                '4th',
            ],
            Resource::load('txt', $this->resources.'/test.txt')
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported file type [yaml]. Please set loader to Rebet\Config\Resource class.
     */
    public function test_load_unsuported()
    {
        Resource::load('yaml', $this->resources.'/test.yaml');
    }

    public function test_load_notfound()
    {
        $this->assertNull(Resource::load('php', $this->resources.'/notfound.php'));
    }
}
