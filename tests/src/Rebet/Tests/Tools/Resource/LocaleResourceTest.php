<?php
namespace Rebet\Tests\Tools\Resource;

use Rebet\Application\App;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Resource\LocaleResource;
use Rebet\Validation\Kind;

class LocaleResourceTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function test_load()
    {
        $resources = LocaleResource::load(App::structure()->resources('/i18n'), 'ja', 'invalid');
        $this->assertSame([], $resources);

        $resources = LocaleResource::load(App::structure()->resources('/i18n'), 'nothing', 'enum');
        $this->assertSame([], $resources);

        $resources = LocaleResource::load(App::structure()->resources('/i18n'), 'ja', 'enum');
        $this->assertSame([
            Gender::class => [
                'label' => [
                    1 => '男性',
                    2 => '女性',
                ],
            ],
            Kind::class => [
                'label' => [
                    1 => '整合性チェック',
                    2 => '依存性チェック',
                    3 => 'その他',
                ],
            ]
        ], $resources);

        $resources = LocaleResource::load(App::structure()->resources('/i18n'), 'de', 'enum');
        $this->assertSame([
            Gender::class => [
                'label' => [
                    1 => 'Männlich',
                    2 => 'Weiblich',
                ],
            ],
        ], $resources);

        $resources = LocaleResource::load([
            App::structure()->resources('/i18n'),
            App::structure()->resources('/adhoc/Tools/Resource/LocaleResource')
        ], 'ja', 'enum');
        $this->assertSame([
            Gender::class => [
                'label' => [
                    1 => '男',
                    2 => '女性',
                ],
            ],
            Kind::class => [
                'label' => [
                    1 => '整合性チェック',
                    2 => '依存性チェック',
                    3 => 'その他',
                ],
            ]
        ], $resources);

        $this->assertSame(
            ['locale' => 'en_US'],
            LocaleResource::load(App::structure()->resources('/adhoc/Tools/Resource/LocaleResource'), 'en_US', 'locale')
        );
        $this->assertSame(
            ['locale' => 'en'],
            LocaleResource::load(App::structure()->resources('/adhoc/Tools/Resource/LocaleResource'), 'en_NZ', 'locale')
        );
        $this->assertSame(
            ['locale' => 'en'],
            LocaleResource::load(App::structure()->resources('/adhoc/Tools/Resource/LocaleResource'), 'en', 'locale')
        );
    }
}
