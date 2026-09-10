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
                    '@root'       => 'TestApp\\Rebet',
                    '@controller' => '@root\\Controller',
                    '@model'      => '@root\\Model',
                    '@C'          => '@controller',
                ],
            ],
        ]);
    }

    public function test_setAlias()
    {
        Namespaces::setAlias('@new', 'TestApp\\New\\Test');
        $this->assertSame('TestApp\\New\\Test\\HelloWorld', Namespaces::resolve('@new\\HelloWorld'));
    }

    public function test_resolve()
    {
        $this->assertSame(null, Namespaces::resolve(null));
        $this->assertSame('HelloWorld', Namespaces::resolve('HelloWorld'));
        $this->assertSame('TestApp\\Rebet\\HelloWorld', Namespaces::resolve('TestApp\\Rebet\\HelloWorld'));
        $this->assertSame('TestApp\\Rebet\\HelloWorld', Namespaces::resolve('\\TestApp\\Rebet\\HelloWorld'));
        $this->assertSame('TestApp\\Rebet\\HelloWorld', Namespaces::resolve('@root\\HelloWorld'));
        $this->assertSame('TestApp\\Rebet\\Controller\\HelloWorld', Namespaces::resolve('@controller\\HelloWorld'));
        $this->assertSame('TestApp\\Rebet\\Model\\HelloWorld', Namespaces::resolve('@model\\HelloWorld'));
        $this->assertSame('TestApp\\Rebet\\Controller\\HelloWorld', Namespaces::resolve('@C\\HelloWorld'));

        Namespaces::setAlias('@new', '\\TestApp\\New');
        $this->assertSame('TestApp\\New\\HelloWorld', Namespaces::resolve('@new\\HelloWorld'));
    }
}
