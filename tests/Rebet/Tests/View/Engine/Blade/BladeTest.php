<?php
namespace Rebet\Tests\View\Engine\Blade;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;

use org\bovigo\vfs\vfsStream;
use Illuminate\View\Compilers\BladeCompiler;
use Rebet\Foundation\App;

class BladeTest extends RebetTestCase
{
    private $root;

    /**
     * @var Rebet\View\Engine\Blade\Blade
     */
    private $blade;

    public function setUp()
    {
        $this->root = vfsStream::setup();
        vfsStream::create(
            [
                'view' => [
                    'layout.blade.php' => <<<'EOS'
Title:
@yield('title')

Section:
@section('section')
    - Main Section
@show
Content:
@yield('content')
EOS
                    ,
                    'child.blade.php' => <<<'EOS'
@extends('layout')
@section('title', 'Unit Test')
@section('section')
@parent
    - Sub Section
@endsection
@section('content')
    This is content.
@endsection
EOS
                    ,
                    'alert.blade.php' => <<<'EOS'
* {{ $title }} *
-----
{{ $slot }}
EOS
                    ,
                    'component.blade.php' => <<<'EOS'
Component Test
@component('alert')
@slot('title')
Forbidden
@endslot
You are not allowed to access this resource!
@endcomponent
EOS
                    ,
                    'component-args.blade.php' => <<<'EOS'
Component Args Test
@component('alert', ['title' => 'Forbidden'])
You are not allowed to access this resource!
@endcomponent
EOS
                    ,
                    'welcome.blade.php' => <<<'EOS'
Hello, {{ $name }}.
EOS
                    ,
                    'json.blade.php' => <<<'EOS'
var app = @json($array);
EOS
                    ,
                    'custom.blade.php' => <<<'EOS'
@env('unittest')
unittest
@endenv
@env(['unittest','local'])
unittest or local
@endenv
@env('production')
production
@else
Not production.
@endenv
EOS
                    ,
                    'empty.blade.php'  => '', // empty file
                ],
                'cache' => [],
            ],
            $this->root
        );

        $this->blade = new Blade([
            'view_path'  => 'vfs://root/view',
            'cache_path' => 'vfs://root/cache',
        ]);
    }

    public function test_compiler()
    {
        $this->assertInstanceOf(BladeCompiler::class, $this->blade->compiler());
    }

    public function test_render()
    {
        $this->assertSame(
            <<<EOS
Title:
Unit Test
Section:
    - Main Section

    - Sub Section
Content:
    This is content.

EOS
            ,
            $this->blade->render('child')
        );

        $this->assertSame(
            <<<EOS
Component Test
* Forbidden *
-----
You are not allowed to access this resource!
EOS
            ,
            $this->blade->render('component')
        );

        $this->assertSame(
            <<<EOS
Component Args Test
* Forbidden *
-----
You are not allowed to access this resource!
EOS
            ,
            $this->blade->render('component-args')
        );

        $this->assertSame(
            <<<EOS
Hello, Samantha.
EOS
            ,
            $this->blade->render('welcome', ['name' => 'Samantha'])
        );

        $this->assertSame(
            <<<EOS
var app = [1,2,3];
EOS
            ,
            $this->blade->render('json', ['array' => [1, 2, 3]])
        );
    }

    public function test_render_directive()
    {
        $this->blade = new Blade([
            'view_path'  => 'vfs://root/view',
            'cache_path' => 'vfs://root/cache',
            'custom' => [
                'if' => [
                    ['env', function ($env) {
                        return in_array(App::getEnv(), (array)$env) ;
                    }],
                ]
            ]
        ]);

        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->blade->render('custom')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->blade->render('custom')
        );
    }

    // /**
    //  * @expectedException \LogicException
    //  * @expectedExceptionMessage Invalid path format: c:/invalid/../../path
    //  */
    // public function test_normalizePath_invalid()
    // {
    // }
}
