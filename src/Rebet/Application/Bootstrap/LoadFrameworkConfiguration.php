<?php
namespace Rebet\Application\Bootstrap;

use Rebet\Application\App;
use Rebet\Application\Database\Pagination\Storage\SessionCursorStorage;
use Rebet\Application\Kernel;
use Rebet\Application\View\Engine\Blade\BladeTagCustomizer;
use Rebet\Application\View\Engine\Twig\TwigTagCustomizer;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Filesystem\Storage;
use Rebet\Http\Request;
use Rebet\Http\Session\Storage\Handler\NativeFileSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Log\Log;
use Rebet\Routing\Router;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;

/**
 * Load Framework Configuration Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LoadFrameworkConfiguration implements Bootstrapper
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(Kernel $kernel)
    {
        Config::framework([
            //---------------------------------------------
            // DateTime Configure
            //---------------------------------------------
            DateTime::class => [
                'default_timezone' => Config::refer(App::class, 'timezone', date_default_timezone_get() ? : 'UTC'),
            ],

            //---------------------------------------------
            // Logging Configure
            //---------------------------------------------
            Log::class => [
                'default_channel' => App::channel() ?? 'stderr',
            ],

            //---------------------------------------------
            // Filesystem Configure
            //---------------------------------------------
            Storage::class => [
                'disks' => [
                    'private' => [
                        'adapter' => [
                            'root' => $kernel->structure()->privateStorage(),
                        ],
                    ],
                    'public' => [
                        'adapter' => [
                            'root' => $kernel->structure()->publicStorage(),
                        ],
                        'filesystem' => [
                            'url' => $kernel->structure()->storageUrl(),
                        ]
                    ],
                ],
            ],

            //---------------------------------------------
            // Http Configure
            //---------------------------------------------
            SessionStorage::class => [
                'handler' => NativeFileSessionHandler::class,
            ],

            //---------------------------------------------
            // Routing Configure
            //---------------------------------------------
            Router::class => [
                'current_channel'          => App::channel(),
                'default_fallback_handler' => $kernel->exceptionHandler(),
            ],

            //---------------------------------------------
            // Database Pagination Configure
            //---------------------------------------------
            Pager::class => [
                'resolver' => function (Pager $pager) {
                    $request = Request::current();
                    return $pager
                        ->page($request->get(App::config('paginate.page_name')) ?? 1)
                        ->size($request->get(App::config('paginate.page_size_name')) ?? Pager::config('default_page_size'))
                        ;
                }
            ],

            Cursor::class => [
                'storage' => SessionCursorStorage::class,
            ],

            //---------------------------------------------
            // View Engine Configure
            //---------------------------------------------
            // Blade template settings
            Blade::class => [
                'customizers' => [BladeTagCustomizer::class.'::customize'],
            ],

            // Twig template settings
            Twig::class => [
                'customizers' => [TwigTagCustomizer::class.'::customize'],
            ],

            //---------------------------------------------
            // Translation Configure
            //---------------------------------------------
            Translator::class => [
                'locale'          => Config::refer(App::class, 'locale'),
                'fallback_locale' => Config::refer(App::class, 'fallback_locale'),
            ],

            FileDictionary::class => [
                'resources' => [
                    'i18n' => [$kernel->structure()->resources('/i18n')],
                ]
            ],
        ]);
    }
}
