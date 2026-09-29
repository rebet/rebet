<?php

declare(strict_types=1);

namespace Rebet\Tests\View;

use Rebet\Application\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Tinker\Tinker;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;
use Rebet\View\EofLineFeed;
use Rebet\View\Exception\ViewRenderFailedException;
use Rebet\View\View;

class ViewTest extends RebetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::application([
            View::class  => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::structure()->views('/blade')],
                'cache_path' => static::makeSubWorkingDir('cache'),
            ],
            Twig::class  => [
                'template_dir' => [App::structure()->views('/twig')],
                'options'      => [
                    // 'cache' => static::makeSubWorkingDir('cache'),
                ],
            ],
        ]);
    }

    public function test_isEnabled(): void
    {
        $this->assertTrue(View::isEnabled());
        Config::application([
            View::class => [
                'engine' => null,
            ],
        ]);
        $this->assertFalse(View::isEnabled());
    }

    public function test___construct(): void
    {
        $this->assertInstanceOf(View::class, new View('welcom'));
        $this->assertInstanceOf(View::class, new View('welcom', null, new Blade(true)));
        $this->assertInstanceOf(View::class, new View('welcom', null, new Twig(true)));
    }

    public function test_of(): void
    {
        $this->assertInstanceOf(View::class, View::of('welcom'));
    }

    public function test_shareAndSharedAndComposerAndClear(): void
    {
        View::composer('/welcome/', function (View $view): void {
            $view->with('name', 'from composer');
        });
        $view = View::of('welcome');
        $this->assertSame('Hello, from composer.', $view->render());

        View::reset();

        View::share('name', 'from share');
        $view = View::of('welcome');
        $this->assertSame('Hello, from share.', $view->render());
        $this->assertSame('from share', View::shared('name'));

        View::reset();

        $this->assertSame(null, View::shared('name'));
    }

    public function test_with(): void
    {
        $this->assertSame('Hello, Bob.', View::of('welcome')->with('name', 'Bob')->render());
        $this->assertSame('Hello, Bob.', View::of('welcome')->with(['name' => 'Bob'])->render());
        $view = View::of('welcome')->with('name', 'Bob');
        $this->assertInstanceOf(Tinker::class, Reflector::get($view, 'data.name', null, true));
    }

    public function test_eof(): void
    {
        $view = View::of('welcome')->eof(EofLineFeed::ONE());
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(EofLineFeed::ONE(), Reflector::get($view, 'eof', null, true));

        $this->assertSame("Hello, Bob.", View::of('welcome')->eof(EofLineFeed::TRIM())->with('name', 'Bob')->render());
        $this->assertSame("Hello, Bob.\n", View::of('welcome')->eof(EofLineFeed::ONE())->with('name', 'Bob')->render());
        $this->assertSame("Hello, Bob.\n\n", View::of('welcome')->eof(EofLineFeed::KEEP())->with('name', 'Bob')->render());
    }

    public function test_render(): void
    {
        $this->assertSame('Hello, Bob.', View::of('welcome')->with('name', 'Bob')->render());
    }

    public function test_render_notExists(): void
    {
        $this->expectException(ViewRenderFailedException::class);
        $this->expectExceptionMessage("The view [nothing] (possible: nothing) render failed because of all of view templates not exists.");

        View::of('nothing')->render();
    }

    public function test_render_notExistsMultiPosible(): void
    {
        $this->expectException(ViewRenderFailedException::class);
        $this->expectExceptionMessage("The view [nothing] (possible: ja/nothing, en/nothing) render failed because of all of view templates not exists.");

        View::of('nothing', function (string $name) { return ["ja/{$name}", "en/{$name}"] ;})->render();
    }

    public function test_exists(): void
    {
        $this->assertTrue(View::of('welcome')->exists());
        $this->assertFalse(View::of('nothing')->exists());
        $this->assertTrue(View::of('nothing', function ($name) { return [$name, 'welcome']; })->exists());
    }

    public function test_getPossibleNames(): void
    {
        $this->assertSame(['nothing'], View::of('nothing')->getPossibleNames());
        $this->assertSame(['nothing', 'welcome'], View::of('nothing', function ($name) { return [$name, 'welcome']; })->getPossibleNames());
        $this->assertSame(['NOTHING'], View::of('nothing', function ($name) { return strtoupper($name); })->getPossibleNames());
    }
}
