<?php
namespace Rebet\Tests\Http\Session;

use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\Bag\AttributeBag;
use Rebet\Http\Session\Storage\Bag\FlashBag;
use Rebet\Http\Session\Storage\Bag\MetadataBag;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class SessionTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Session::class, new Session());
    }

    public function test_clear()
    {
        $this->assertSame(null, Session::current());
        $session   = new Session();
        $session->set('foo', 'Foo');
        $attribute = $session->attribute();
        $this->assertSame($session, Session::current());
        $this->assertSame(['foo' => 'Foo'], $attribute->all());
        Session::clear();
        $this->assertSame(null, Session::current());
        $this->assertSame([], $attribute->all());
    }

    public function test_current()
    {
        $this->assertSame(null, Session::current());
        $session = new Session();
        $this->assertSame($session, Session::current());
    }

    public function test_has()
    {
        $session = new Session();
        $session->set('foo', 'Foo');
        $session->set('baz', ['a' => 'A']);
        $this->assertSame(true, $session->has('foo'));
        $this->assertSame(false, $session->has('bar'));
        $this->assertSame(true, $session->has('baz'));
        $this->assertSame(true, $session->has('baz.a'));
        $this->assertSame(false, $session->has('baz.b'));
    }

    public function test_get()
    {
        $session = new Session();
        $session->set('foo', 'Foo');
        $session->set('baz', ['a' => 'A']);
        $this->assertSame('Foo', $session->get('foo'));
        $this->assertSame(null, $session->get('bar'));
        $this->assertSame(['a' => 'A'], $session->get('baz'));
        $this->assertSame('A', $session->get('baz.a'));
        $this->assertSame(null, $session->get('baz.b'));
    }

    public function test_set()
    {
        $session = new Session();

        $this->assertSame(null, $session->get('foo'));
        $this->assertInstanceOf(Session::class, $session->set('foo', 'Foo'));
        $this->assertSame('Foo', $session->get('foo'));

        $this->assertSame(null, $session->get('baz'));
        $session->set('baz', ['a' => 'A']);
        $this->assertSame(['a' => 'A'], $session->get('baz'));

        $this->assertSame(null, $session->get('baz.b'));
        $session->set('baz.b', 'B');
        $this->assertSame('B', $session->get('baz.b'));
        $this->assertSame(['a' => 'A', 'b' => 'B'], $session->get('baz'));
    }

    public function test_remove()
    {
        $session = new Session();
        $session->set('foo', 'Foo');
        $session->set('baz', ['a' => 'A', 'b' => 'B']);

        $this->assertSame('Foo', $session->get('foo'));
        $this->assertSame('Foo', $session->remove('foo'));
        $this->assertSame(null, $session->get('foo'));

        $this->assertSame(null, $session->get('bar'));
        $this->assertSame(null, $session->remove('bar'));
        $this->assertSame(null, $session->get('bar'));

        $this->assertSame(['a' => 'A', 'b' => 'B'], $session->get('baz'));
        $this->assertSame('B', $session->get('baz.b'));
        $this->assertSame('B', $session->remove('baz.b'));
        $this->assertSame(null, $session->get('baz.b'));
        $this->assertSame(['a' => 'A'], $session->get('baz'));
        $this->assertSame(['a' => 'A'], $session->remove('baz'));
        $this->assertSame(null, $session->get('baz'));
    }

    public function test_attribute()
    {
        $session = new Session();
        $this->assertInstanceOf(AttributeBag::class, $session->attribute());
    }

    public function test_flash()
    {
        $session = new Session();
        $this->assertInstanceOf(FlashBag::class, $session->flash());
    }

    public function test_meta()
    {
        $session = new Session();
        $this->assertInstanceOf(MetadataBag::class, $session->meta());
    }

    public function test_startAndIsStarted()
    {
        $session = new Session();
        $this->assertSame('', $session->id());
        $this->assertSame(false, $session->isStarted());
        $session->start();
        $this->assertNotSame('', $session->id());
        $this->assertSame(true, $session->isStarted());
    }

    public function test_invalidate()
    {
        $session = new Session();
        $session->start();
        $session->set('foo', 'bar');
        $this->assertSame('bar', $session->get('foo'));

        $id = $session->id();
        $session->invalidate();
        $this->assertNotSame($id, $session->id());
        $this->assertSame(null, $session->get('foo'));

        $session->set('foo', 'bar');
        $this->assertSame('bar', $session->get('foo'));

        $id = $session->id();
        $session->invalidate(60);
        $this->assertNotSame($id, $session->id());
        $this->assertSame(null, $session->get('foo'));
    }

    public function test_migrate()
    {
        $session = new Session();
        $session->start();
        $session->set('foo', 'bar');
        $this->assertSame('bar', $session->get('foo'));

        $id = $session->id();
        $session->migrate(false);
        $this->assertNotSame($id, $session->id());
        $this->assertSame('bar', $session->get('foo'));

        $id = $session->id();
        $session->migrate(true);
        $this->assertNotSame($id, $session->id());
        $this->assertSame(null, $session->get('foo'));
    }

    public function test_save()
    {
        $mock = $this->getMockBuilder(SessionStorageInterface::class)->getMock();
        $mock->expects($this->once())->method('save');
        $session = new Session($mock);
        $this->assertInstanceOf(Session::class, $session->save());
    }

    public function test_id()
    {
        $mock = $this->getMockBuilder(SessionStorageInterface::class)->getMock();
        $mock->expects($this->once())->method('getId')->willReturn('ANY_SESSION_ID');
        $session = new Session($mock);
        $this->assertSame('ANY_SESSION_ID', $session->id());

        $session = new Session();
        $this->assertSame('', $session->id());
        $this->assertInstanceOf(Session::class, $session->id('new_Id'));
        $this->assertSame('new_Id', $session->id());
    }

    public function test_tokenAndGenerateTokenAndVerifyToken()
    {
        $session = new Session();
        $this->assertSame(null, $session->token());

        $token = $session->generateToken();
        $this->assertNotSame(null, $token);
        $this->assertSame($token, $session->token());
        $this->assertSame(40, mb_strlen($token));
        $this->assertSame(true, $session->verifyToken($token)); // 1st time check
        $this->assertSame(true, $session->verifyToken($token)); // 2nd time check
        $this->assertSame(false, $session->verifyToken('incorrect'.$token));

        $this->assertSame(null, $session->token('user', 'edit'));
        $onetime_token = $session->generateToken('user', 'edit');
        $this->assertNotSame(null, $onetime_token);
        $this->assertSame($onetime_token, $session->token('user', 'edit'));
        $this->assertSame(40, mb_strlen($onetime_token));
        $this->assertSame(true, $session->verifyToken($onetime_token, 'user', 'edit'));  // 1st time check
        $this->assertSame(false, $session->verifyToken($onetime_token, 'user', 'edit')); // 2nd time check

        $onetime_token = $session->generateToken('user', 'edit');
        $this->assertSame(false, $session->verifyToken('incorrect'.$onetime_token, 'user', 'edit')); // 1st time check
        $this->assertSame(false, $session->verifyToken($onetime_token, 'user', 'edit'));             // 2nd time check

        $ot1 = $session->generateToken('article', 'edit', 1);
        $ot2 = $session->generateToken('article', 'edit', 2);
        $this->assertSame(true, $session->verifyToken($ot1, 'article', 'edit', 1));
        $this->assertSame(true, $session->verifyToken($ot2, 'article', 'edit', 2));

        $this->assertSame(true, $session->verifyToken($token));
    }

    private function setInheritDataTo(Session $session)
    {
        $session->saveInheritData('input', ['a' => 'A']);
        $session->saveInheritData('input', ['b' => 'B']);
        $session->saveInheritData('input', ['b' => 'b', 'c' => 'c'], '/user/edit');
        $session->saveInheritData('input', ['c' => 'C'], '/user/*');
        $session->saveInheritData('input', ['d' => 'D'], ['/blog/register', '/blog/copy']);
    }

    public function test_saveAndLoadInheritData()
    {
        $session = new Session();
        $this->setInheritDataTo($session);
        $this->assertSame(
            [
                [['*'], ['a' => 'A']],
                [['*'], ['b' => 'B']],
                [['/user/edit'], ['b' => 'b', 'c' => 'c']],
                [['/user/*'], ['c' => 'C']],
                [['/blog/register', '/blog/copy'], ['d' => 'D']],
            ],
            $session->flash()->peek('_inherit_input')
        );

        $this->assertSame([], $session->loadInheritData('invalid', '/'));

        $this->assertSame(
            [
                'a' => 'A',
                'b' => 'B',
            ],
            $session->loadInheritData('input', '/')
        );
        $this->assertSame(null, $session->flash()->peek('_inherit_input'));

        $this->setInheritDataTo($session);
        $this->assertSame(
            [
                'a' => 'A',
                'b' => 'b',
                'c' => 'C',
            ],
            $session->loadInheritData('input', '/user/edit')
        );
        $this->assertSame(null, $session->flash()->peek('_inherit_input'));

        $this->setInheritDataTo($session);
        $this->assertSame(
            [
                'a' => 'A',
                'b' => 'B',
                'c' => 'C',
            ],
            $session->loadInheritData('input', '/user/register')
        );
        $this->assertSame(null, $session->flash()->peek('_inherit_input'));

        $this->setInheritDataTo($session);
        $this->assertSame(
            [
                'a' => 'A',
                'b' => 'B',
                'd' => 'D',
            ],
            $session->loadInheritData('input', '/blog/register')
        );
        $this->assertSame(null, $session->flash()->peek('_inherit_input'));
    }
}
