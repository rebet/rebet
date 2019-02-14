<?php
namespace Rebet\Tests\Foundation\View\Engine;

use org\bovigo\vfs\vfsStream;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Engine;

abstract class EngineCustomizerTestCase extends RebetTestCase
{
    private $root;

    private $engine;

    abstract protected function createEngine() : Engine;

    public function setUp()
    {
        parent::setUp();
        $this->signout();
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'cache' => [],
            ],
            $this->root
        );

        $this->engine = $this->createEngine();
    }

    public function test_render_env()
    {
        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->engine->render('custom/env')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->engine->render('custom/env')
        );
    }

    public function test_render_prefix()
    {
        $this->assertSame('/controller/action/arg1', $this->engine->render('custom/prefix'));
        $this->assertSame('/controller/action/arg1', $this->engine->render('custom/prefix', ['prefix' => null]));
        $this->assertSame('/rebet/controller/action/arg1', $this->engine->render('custom/prefix', ['prefix' => '/rebet']));
    }

    public function test_render_is()
    {
        $this->assertSame(
            <<<EOS
Guest

EOS
            ,
            $this->engine->render('custom/is')
        );

        $request = $this->createRequestMock('/');
        $this->signin($request);

        $this->assertSame(
            <<<EOS
user
admin or user
Not Guest.

EOS
            ,
            $this->engine->render('custom/is')
        );
        $this->signout();

        $this->signin($request, 'user.editable@rebet.local', 'user.editable');
        $this->assertSame(
            <<<EOS
user
user and editable
admin or user
Not Guest.

EOS
            ,
            $this->engine->render('custom/is')
        );
        $this->signout();

        $this->signin($request, 'admin@rebet.local', 'admin');
        $this->assertSame(
            <<<EOS
admin
admin or user
Not Guest.

EOS
            ,
            $this->engine->render('custom/is')
        );
    }
}
