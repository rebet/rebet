<?php
namespace Rebet\Tests\Config;

use Rebet\Config\EnvResource;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;

class EnvResourceTest extends RebetTestCase
{
    private $resources;
    
    public function setUp()
    {
        parent::setUp();
        $this->resources = App::path('/resources/Config/EnvResource');
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
            EnvResource::load($this->resources, 'test', 'unittest')
        );

        $this->assertSame(
            [
                'int'    => 1,
                'string' => 'a',
                'array'  => [1, 2, 3],
                'map'    => [
                    'int'    => 1,
                    'string' => 'a',
                    'array'  => [1, 2, 3],
                ],
            ],
            EnvResource::load($this->resources, 'test', 'production')
        );

        $this->assertSame(
            [
                'int'    => 1,
                'string' => 'a',
                'array'  => [1, 2, 3],
                'map'    => [
                    'int'    => 1,
                    'string' => 'a',
                    'array'  => [1, 2, 3],
                ],
                'extra' => 1,
            ],
            EnvResource::load($this->resources, ['test', 'extra'], 'production')
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
            EnvResource::load($this->resources, 'test', 'unittest', 'ini')
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
            EnvResource::load($this->resources, 'test', 'unittest', 'json')
        );
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     */
    public function test_load_notfound()
    {
        EnvResource::load($this->resources, 'test', 'unittest', 'txt');
        $this->fail("Never execute.");
    }
}
