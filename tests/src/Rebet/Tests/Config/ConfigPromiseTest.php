<?php
namespace Rebet\Tests\Config;

use Rebet\Config\ConfigPromise;
use Rebet\Tests\RebetTestCase;

class ConfigPromiseTest extends RebetTestCase
{
    private $promise_once;
    private $promise_every;

    public function setUp()
    {
        parent::setUp();
        \putenv('PROMISE_TEST=');
        $this->promise_once  = new ConfigPromise(function () {
            return \getenv('PROMISE_TEST') ?: 'default';
        });
        $this->promise_every = new ConfigPromise(function () {
            return \getenv('PROMISE_TEST') ?: 'default';
        }, false);
    }

    public function tearDown()
    {
        \putenv('PROMISE_TEST=');
    }

    public function test_get()
    {
        \putenv('PROMISE_TEST=1');
        $this->assertSame('1', $this->promise_once->get());
        $this->assertSame('1', $this->promise_every->get());

        \putenv('PROMISE_TEST=2');
        $this->assertSame('1', $this->promise_once->get());
        $this->assertSame('2', $this->promise_every->get());
    }

    public function test___toString()
    {
        $this->assertSame("<Promise: once>", $this->promise_once->__toString());
        $this->assertSame("<Promise: dynamic>", $this->promise_every->__toString());

        \putenv('PROMISE_TEST=1');
        $this->promise_once->get();

        $this->assertSame("1", $this->promise_once->__toString());
        $this->assertSame("<Promise: dynamic>", $this->promise_every->__toString());
    }
}
