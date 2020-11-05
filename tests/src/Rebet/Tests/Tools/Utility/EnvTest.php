<?php
namespace Rebet\Tests\Tools\Utility;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Utility\Env;

class EnvTest extends RebetTestCase
{
    public function test_get()
    {
        putenv('FOO');
        $this->assertSame(null, Env::get('FOO'));
        $this->assertSame('default', Env::get('FOO', 'default'));
        $this->assertSame(false, Env::get('FOO', false));
        putenv('FOO=');
        $this->assertSame('', Env::get('FOO'));
        $this->assertSame('', Env::get('FOO', 'default'));
        putenv('FOO=true');
        $this->assertSame(true, Env::get('FOO'));
        $this->assertSame(true, Env::get('FOO', false));
        putenv('FOO=false');
        $this->assertSame(false, Env::get('FOO'));
        $this->assertSame(false, Env::get('FOO', true));
        putenv('FOO=null');
        $this->assertSame(null, Env::get('FOO'));
        $this->assertSame('default', Env::get('FOO', 'default'));
        putenv('FOO="true"');
        $this->assertSame("true", Env::get('FOO'));
        putenv('FOO=string');
        $this->assertSame("string", Env::get('FOO'));
    }

    public function test_promise()
    {
        putenv('FOO');
        $promise = Env::promise('FOO');
        $this->assertSame(null, $promise->get());
        putenv('FOO=foo');
        $this->assertSame(null, $promise->get());

        putenv('FOO=foo');
        $promise = Env::promise('FOO');
        $this->assertSame('foo', $promise->get());
        putenv('FOO=bar');
        $this->assertSame('foo', $promise->get());

        putenv('FOO');
        $promise = Env::promise('FOO', 'default');
        $this->assertSame('default', $promise->get());
        putenv('FOO=foo');
        $this->assertSame('default', $promise->get());

        putenv('FOO');
        $promise = Env::promise('FOO', 'default', false);
        $this->assertSame('default', $promise->get());
        putenv('FOO=foo');
        $this->assertSame('foo', $promise->get());
        putenv('FOO=true');
        $this->assertSame(true, $promise->get());
        putenv('FOO=null');
        $this->assertSame('default', $promise->get());
        putenv('FOO');
        $this->assertSame('default', $promise->get());
    }
}
