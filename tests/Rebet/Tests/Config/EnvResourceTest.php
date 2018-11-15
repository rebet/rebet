<?php
namespace Rebet\Tests\Config;

use Rebet\Config\EnvResource;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;

class EnvResourceTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
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
            EnvResource::load(App::path('/resources/config'), 'test', 'unittest')
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
            EnvResource::load(App::path('/resources/config'), 'test', 'production')
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
            EnvResource::load(App::path('/resources/config'), ['test', 'extra'], 'production')
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
            EnvResource::load(App::path('/resources/config'), 'test', 'unittest', 'ini')
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
            EnvResource::load(App::path('/resources/config'), 'test', 'unittest', 'json')
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function test_load_notfound()
    {
        EnvResource::load(App::path('/resources/config'), 'test', 'unittest', 'txt');
        $this->fail("Never execute.");
    }
}
