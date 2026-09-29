<?php

declare(strict_types=1);

namespace Rebet\Tests\View\Engine\Twig;

use Override;
use Rebet\Application\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\View\Engine\Twig\Twig;

class TwigTest extends RebetTestCase
{
    /**
     * @var Twig
     */
    private $twig;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Config::application([
            Twig::class => [
                'template_dir' => [App::structure()->views('/twig')],
                'options'      => [
                    // 'cache' => static::makeSubWorkingDir('cache'),
                ],
            ],
        ]);

        $this->twig = new Twig(true);
    }

    public function test_getPaths(): void
    {
        $this->assertTrue(in_array(App::structure()->views('/twig'), $this->twig->getPaths()));
    }

    public function test_prependPath(): void
    {
        $paths = $this->twig->getPaths();
        $this->twig->prependPath($path_1 = App::structure()->views(''));
        $new_paths = $this->twig->getPaths();
        $this->assertSame(array_merge([$path_1], $paths), $new_paths);
    }

    public function test_appendPath(): void
    {
        $paths = $this->twig->getPaths();
        $this->twig->appendPath($path_1 = App::structure()->views(''));
        $new_paths = $this->twig->getPaths();
        $this->assertSame(array_merge($paths, [$path_1]), $new_paths);
    }

    public function test_exists(): void
    {
        $this->assertTrue($this->twig->exists('welcome'));
        $this->assertTrue($this->twig->exists('custom/env'));
        $this->assertFalse($this->twig->exists('nothing'));
    }

    public function test_render(): void
    {
        $this->assertSame(
            <<<EOS
                Hello, Samantha.
                EOS,
            $this->twig->render('welcome', ['name' => 'Samantha']),
        );
    }
}
