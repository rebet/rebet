<?php
namespace Rebet\Tests\Config;

use Rebet\Config\LocaleResource;
use Rebet\Foundation\App;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\RebetTestCase;

class LocaleResourceTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_load()
    {
        $resources = LocaleResource::load(App::path('/resources/i18n'), 'ja', 'invalid');
        $this->assertSame([], $resources);

        $resources = LocaleResource::load(App::path('/resources/i18n'), 'nothing', 'enum');
        $this->assertSame([], $resources);

        $resources = LocaleResource::load(App::path('/resources/i18n'), 'ja', 'enum');
        $this->assertSame([
            Gender::class => [
                'label' => [
                    1 => '男性',
                    2 => '女性',
                ],
            ],
        ], $resources);

        $resources = LocaleResource::load(App::path('/resources/i18n'), 'de', 'enum');
        $this->assertSame([
            Gender::class => [
                'label' => [
                    1 => 'Männlich',
                    2 => 'Weiblich',
                ],
            ],
        ], $resources);

        $resources = LocaleResource::load([
            App::path('/resources/i18n'),
            App::path('/resources/tests/Config/LocaleResource')
        ], 'ja', 'enum');
        $this->assertSame([
            Gender::class => [
                'label' => [
                    1 => '男',
                    2 => '女性',
                ],
            ],
        ], $resources);
    }
}
