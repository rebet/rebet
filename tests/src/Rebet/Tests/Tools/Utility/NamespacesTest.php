<?php
namespace Rebet\Tests\Tools\Utility;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Utility\Namespaces;

class NamespacesTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        Config::application([
            Namespaces::class => [
                'aliases' => [
                    '@root'       => 'App\\Rebet',
                    '@controller' => '@root\\Controller',
                    '@model'      => '@root\\Model',
                    '@C'          => '@controller',
                ],
            ],
        ]);
    }

    public function test_setAlias()
    {
        Namespaces::setAlias('@new', 'App\\New\\Test');
        $this->assertSame('App\\New\\Test\\HelloWorld', Namespaces::resolve('@new\\HelloWorld'));
    }

    public function test_resolve()
    {
        $this->assertSame(null, Namespaces::resolve(null));
        $this->assertSame('HelloWorld', Namespaces::resolve('HelloWorld'));
        $this->assertSame('App\\Rebet\\HelloWorld', Namespaces::resolve('App\\Rebet\\HelloWorld'));
        $this->assertSame('App\\Rebet\\HelloWorld', Namespaces::resolve('\\App\\Rebet\\HelloWorld'));
        $this->assertSame('App\\Rebet\\HelloWorld', Namespaces::resolve('@root\\HelloWorld'));
        $this->assertSame('App\\Rebet\\Controller\\HelloWorld', Namespaces::resolve('@controller\\HelloWorld'));
        $this->assertSame('App\\Rebet\\Model\\HelloWorld', Namespaces::resolve('@model\\HelloWorld'));
        $this->assertSame('App\\Rebet\\Controller\\HelloWorld', Namespaces::resolve('@C\\HelloWorld'));

        Namespaces::setAlias('@new', '\\App\\New');
        $this->assertSame('App\\New\\HelloWorld', Namespaces::resolve('@new\\HelloWorld'));
    }
}
