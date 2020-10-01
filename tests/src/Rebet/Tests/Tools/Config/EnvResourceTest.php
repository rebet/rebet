<?php
namespace Rebet\Tests\Tools\Config;

use Rebet\Application\App;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Config\EnvResource;
use Rebet\Tests\RebetTestCase;

class EnvResourceTest extends RebetTestCase
{
    private $resources;

    protected function setUp() : void
    {
        parent::setUp();
        $this->resources = App::structure()->resources('/adhoc/Config/EnvResource');
    }

    public function test_load()
    {
        $this->assertSame(
            [
                'extra'  => 1,
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
            EnvResource::load('unittest', $this->resources)
        );

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
            EnvResource::load('unittest', $this->resources, 'test')
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
            EnvResource::load('production', $this->resources, 'test')
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
            EnvResource::load('production', $this->resources, ['test', 'extra'])
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
            EnvResource::load('unittest', $this->resources, 'test', 'ini')
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
            EnvResource::load('unittest', $this->resources, 'test', 'json')
        );
    }

    public function test_load_notfound()
    {
        $this->expectException(LogicException::class);

        EnvResource::load('unittest', $this->resources, 'test', 'txt');
    }
}
