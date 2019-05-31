<?php
namespace Rebet\Tests\View;

use Rebet\Common\Reflector;
use Rebet\Config\Config;
use Rebet\Foundation\App;
use Rebet\Stream\Stream;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;
use Rebet\View\EofLineFeed;
use Rebet\View\View;

class ViewTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => App::path('/resources/views/blade'),
                'cache_path' => 'vfs://root/cache',
            ],
        ]);
    }

    protected function blade() : Blade
    {
        return new Blade([
            'view_path'  => App::path('/resources/views/blade'),
            'cache_path' => 'vfs://root/cache',
        ], true);
    }

    protected function twig() : Twig
    {
        return new Twig([
            'template_dir' => App::path('/resources/views/twig'),
            'options'      => [
                // 'cache' => 'vfs://root/cache',
            ],
        ], true);
    }

    public function test___construct()
    {
        $this->assertInstanceOf(View::class, new View('welcom'));
        $this->assertInstanceOf(View::class, new View('welcom', null, $this->blade()));
        $this->assertInstanceOf(View::class, new View('welcom', null, $this->twig()));
    }

    public function test_of()
    {
        $this->assertInstanceOf(View::class, View::of('welcom'));
    }

    public function test_shareAndSharedAndComposerAndClear()
    {
        View::composer('/welcome/', function (View $view) {
            $view->with('name', 'from composer');
        });
        $view = View::of('welcome');
        $this->assertSame('Hello, from composer.', $view->render());

        View::clear();

        View::share('name', 'from share');
        $view = View::of('welcome');
        $this->assertSame('Hello, from share.', $view->render());
        $this->assertSame('from share', View::shared('name'));

        View::clear();

        $this->assertSame(null, View::shared('name'));
    }

    public function test_with()
    {
        $this->assertSame('Hello, Bob.', View::of('welcome')->with('name', 'Bob')->render());
        $this->assertSame('Hello, Bob.', View::of('welcome')->with(['name' => 'Bob'])->render());
        $view = View::of('welcome')->with('name', 'Bob');
        $this->assertInstanceOf(Stream::class, Reflector::get($view, 'data.name', null, true));
    }

    public function test_eof()
    {
        $view = View::of('welcome')->eof(EofLineFeed::ONE());
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(EofLineFeed::ONE(), Reflector::get($view, 'eof', null, true));

        $this->assertSame("Hello, Bob.", View::of('welcome')->eof(EofLineFeed::TRIM())->with('name', 'Bob')->render());
        $this->assertSame("Hello, Bob.\n", View::of('welcome')->eof(EofLineFeed::ONE())->with('name', 'Bob')->render());
        $this->assertSame("Hello, Bob.\n\n", View::of('welcome')->eof(EofLineFeed::KEEP())->with('name', 'Bob')->render());
    }

    public function test_render()
    {
        $this->assertSame('Hello, Bob.', View::of('welcome')->with('name', 'Bob')->render());
    }

    /**
     * @expectedException Rebet\View\Exception\ViewRenderFailedException
     * @expectedExceptionMessage The view [nothing] (possible: nothing) render failed because of all of view templates not exists.
     */
    public function test_render_notExists()
    {
        View::of('nothing')->render();
    }

    /**
     * @expectedException Rebet\View\Exception\ViewRenderFailedException
     * @expectedExceptionMessage The view [nothing] (possible: ja/nothing, en/nothing) render failed because of all of view templates not exists.
     */
    public function test_render_notExistsMultiPosible()
    {
        View::of('nothing', function (string $name) { return ["ja/{$name}", "en/{$name}"] ;})->render();
    }

    public function test_exists()
    {
        $this->assertTrue(View::of('welcome')->exists());
        $this->assertFalse(View::of('nothing')->exists());
        $this->assertTrue(View::of('nothing', function ($name) { return [$name, 'welcome']; })->exists());
    }

    public function test_getPossibleNames()
    {
        $this->assertSame(['nothing'], View::of('nothing')->getPossibleNames());
        $this->assertSame(['nothing', 'welcome'], View::of('nothing', function ($name) { return [$name, 'welcome']; })->getPossibleNames());
        $this->assertSame(['NOTHING'], View::of('nothing', function ($name) { return strtoupper($name); })->getPossibleNames());
    }
}
