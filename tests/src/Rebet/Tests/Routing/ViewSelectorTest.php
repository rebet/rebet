<?php
namespace Rebet\Tests\Routing;

use Rebet\Tools\Strings;
use Rebet\Tools\Config\Config;
use Rebet\Application\App;
use Rebet\Http\Request;
use Rebet\Routing\Route\ViewRoute;
use Rebet\Routing\ViewSelector;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class ViewSelectorTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Blade::clear();
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::structure()->views('/blade/selector')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);
    }

    public function test___construct()
    {
        $request = $this->createRequestMock('/');
        $this->assertInstanceOf(ViewSelector::class, new ViewSelector());
    }

    public function dataViewDirectoryChangers() : array
    {
        return [
            ["Hello, Bob.\nTest for directory change type view selector.", 'en', '/welcome/Bob'],
            ["Hello, Bob.\nTest for directory change type view selector.", 'en_AU', '/welcome/Bob'],
            ["Hello, Bob.\nTest for directory change type view selector.", 'de', '/welcome/Bob'],
            ["こんにちは、Bob。\nディレクトリ変更形式のビューセレクターテスト用。", 'ja', '/welcome/Bob']
        ];
    }

    /**
     * @dataProvider dataViewDirectoryChangers
     */
    public function test_view_directoryChanger($expect, $locale, $url)
    {
        Config::application([
            ViewSelector::class => [
                'changer' => function ($view_name, $request, $user) {
                    $locale = App::getLocale();
                    return ["{$locale}/{$view_name}", Strings::latrim($locale, '_').'/'.$view_name, App::getFallbackLocale().'/'.$view_name, ];
                }
            ]
        ]);

        App::setLocale($locale);
        $route   = new ViewRoute('/welcome/{name}', 'welcome');
        $request = Request::create($url);
        $route->match($request);
        $response = $route->handle($request);
        $this->assertSame($expect, $response->getContent());
    }

    public function dataViewFilenameChangers() : array
    {
        return [
            ["Hello, Bob for Mobile.\nTest for file name change type view selector.", "Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1", '/welcome/Bob'],
            ["Hello, Bob for PC (not mobile).\nTest for file name change type view selector.", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100", '/welcome/Bob'],
        ];
    }

    /**
     * @dataProvider dataViewFilenameChangers
     */
    public function test_view_filenameChanger($expect, $ua, $url)
    {
        Config::application([
            ViewSelector::class => [
                'changer' => function ($view_name, Request $request, $user) {
                    $device = $request->getUserAgent()->isMobile() ? 'sp' : 'pc' ;
                    return "{$view_name}_{$device}";
                }
            ]
        ]);

        $route   = new ViewRoute('/welcome/{name}', 'welcome');
        $request = Request::create($url, 'GET', [], [], [], ['HTTP_USER_AGENT' => $ua]);
        $route->match($request);
        $response = $route->handle($request);
        $this->assertSame($expect, $response->getContent());
    }
}
