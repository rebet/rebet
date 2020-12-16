<?php
namespace Rebet\Tests\Mail\Mime;

use Rebet\Mail\Mail;
use Rebet\Mail\Mime\HeaderSet;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Exception\LogicException;
use Swift_DependencyContainer;
use Swift_Mime_Header;
use Swift_Mime_SimpleHeaderSet;

class HeaderSetTest extends RebetTestCase
{
    /**
     * @var HeaderSet
     */
    protected $headers;

    protected function setUp() : void
    {
        parent::setUp();
        $this->headers = Mail::text()->headers();
    }

    public function test___construct()
    {
        $this->assertInstanceOf(HeaderSet::class, new HeaderSet(new Mail()));
    }

    public function test_endheader()
    {
        $this->assertInstanceOf(Mail::class, $this->headers->endheader());
    }

    public function test_addAndHas()
    {
        $name  = 'X-Add-Test-Text';
        $value = 'text';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->add($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: {$value}\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Date';
        $value = DateTime::now();
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->add($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: {$value->format(DateTime::RFC2822)}\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Parameterized-1';
        $value = 'Test';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->add($name, $value, []));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Parameterized-1: Test\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Parameterized-2';
        $value = 'Test';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->add($name, $value, ['foo' => 'bar']));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Parameterized-2: Test; foo=bar\r\n", $this->headers->get($name)->toString());
    }

    public function test_add_invalid()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid value(=null) was given for 'X-Null' header, if the value can be null then you should use addXxxxHeader() method.");
        $this->headers->add('X-Null', null);
    }

    public function test_addTextHeaderAndHas()
    {
        $name  = 'X-Add-Test-Text';
        $value = 'text';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addTextHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: {$value}\r\n", $this->headers->get($name)->toString());
    }

    public function test_addDateHeaderAndHas()
    {
        $name  = 'X-Add-Test-Date';
        $value = DateTime::now();
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addDateHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: {$value->format(DateTime::RFC2822)}\r\n", $this->headers->get($name)->toString());
    }

    public function test_addIdHeaderAndHas()
    {
        $name  = 'X-Add-Test-ID';
        $value = 'id1@domain';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addIdHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: <{$value}>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-ID-Multi';
        $value = ['id1@domain', 'id2@domain'];
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addIdHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-ID-Multi: <id1@domain> <id2@domain>\r\n", $this->headers->get($name)->toString());
    }

    public function test_addMailboxHeaderAndHas()
    {
        $name  = 'X-Add-Test-Mailbox-1';
        $value = 'user1@domain.com';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: {$value}\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Mailbox-2';
        $value = 'User One <user1@domain.com>';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: User One <user1@domain.com>\r\n", $this->headers->get($name)->toString());
        $this->assertSame(['user1@domain.com' => 'User One'], $this->headers->get($name)->getFieldBodyModel());

        $name  = 'X-Add-Test-Mailbox-3';
        $value = ['user1@domain.com' => 'User One'];
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: User One <user1@domain.com>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Mailbox-4';
        $value = ['user1@domain.com' => 'マルチバイト文字'];
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: =?utf-8?B?44Oe44Or44OB44OQ44Kk44OI5paH5a2X?=\r\n =?utf-8?B??= <user1@domain.com>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Mailbox-5';
        $value = 'マルチバイト文字 <user1@domain.com>';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: =?utf-8?B?44Oe44Or44OB44OQ44Kk44OI5paH5a2X?=\r\n =?utf-8?B??= <user1@domain.com>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Mailbox-Multi-1';
        $value = ['user1@domain.com', 'user2@domain.com'];
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Mailbox-Multi-1: user1@domain.com, user2@domain.com\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Mailbox-Multi-2';
        $value = ['user1@domain.com', 'User Two <user2@domain.com>', 'user3@domain.com' => 'User Three'];
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addMailboxHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Mailbox-Multi-2: user1@domain.com, User Two <user2@domain.com>,\r\n User Three <user3@domain.com>\r\n", $this->headers->get($name)->toString());
    }

    public function test_addPathHeaderAndHas()
    {
        $name  = 'X-Add-Test-Path-1';
        $value = null;
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addPathHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Path-1: \r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Path-2';
        $value = '';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addPathHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Path-2: <>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Path-3';
        $value = 'user1@domain.com';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addPathHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Path-3: <user1@domain.com>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Path-4';
        $value = 'User Two <user2@domain.com>';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addPathHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Path-4: <user2@domain.com>\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Path-5';
        $value = ['user2@domain.com' => 'User Two'];
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addPathHeader($name, $value));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Path-5: <user2@domain.com>\r\n", $this->headers->get($name)->toString());
    }

    public function test_addParameterizedHeaderAndHas()
    {
        $name  = 'X-Add-Test-Parameterized-1';
        $value = 'Test';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addParameterizedHeader($name, $value, []));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Parameterized-1: Test\r\n", $this->headers->get($name)->toString());

        $name  = 'X-Add-Test-Parameterized-2';
        $value = 'Test';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->addParameterizedHeader($name, $value, ['foo' => 'bar']));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("X-Add-Test-Parameterized-2: Test; foo=bar\r\n", $this->headers->get($name)->toString());
    }

    public function test_set()
    {
        $factory = Swift_DependencyContainer::getInstance()->lookup('mime.headerfactory');

        $name ='X-Foo';
        $this->assertFalse($this->headers->has($name));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->set($factory->createTextHeader($name, 'bar')));
        $this->assertTrue($this->headers->has($name));
        $this->assertSame("{$name}: bar\r\n", $this->headers->get($name)->toString());
    }

    public function test_get()
    {
        $this->assertSame(null, $this->headers->get('X-Foo'));
        $header = $this->headers->add('X-Foo', 'bar')->get('X-Foo');
        $this->assertInstanceOf(Swift_Mime_Header::class, $header);
        $this->assertSame("X-Foo: bar\r\n", $header->toString());

        $header = $this->headers->add('X-Foo', 'baz')->get('X-Foo');
        $this->assertIsArray($header);
        $this->assertSame("X-Foo: bar\r\nX-Foo: baz\r\n", implode('', $header));
    }

    public function test_all()
    {
        $this->assertIsArray($this->headers->all());
        $this->assertStringContainsString('MIME-Version: 1.0', implode('', $this->headers->all()));
    }

    public function test_list()
    {
        $this->assertContains('message-id', $this->headers->list());
        $this->assertContains('date', $this->headers->list());
    }

    public function test_remove()
    {
        $this->headers->add('X-Foo', '0')->add('X-Foo', '1')->add('X-Foo', '2');
        $this->assertSame("X-Foo: 0\r\nX-Foo: 1\r\nX-Foo: 2\r\n", implode('', $this->headers->get('X-Foo')));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->remove('X-Foo', 1));
        $this->assertSame("X-Foo: 0\r\nX-Foo: 2\r\n", implode('', $this->headers->get('X-Foo')));
        $this->assertTrue($this->headers->has('X-Foo'));
        $this->assertInstanceOf(HeaderSet::class, $this->headers->remove('X-Foo'));
        $this->assertFalse($this->headers->has('X-Foo'));
    }

    public function test_defineOrdering()
    {
        $current_order = $this->headers->list();
        $this->assertInstanceOf(HeaderSet::class, $this->headers->defineOrdering(...array_reverse($current_order)));
        $reverse_order = $this->headers->list();
        $this->assertNotSame($current_order, $reverse_order);
        $this->assertSame($current_order, array_reverse($reverse_order));
    }

    public function test_setAlwaysDisplayed()
    {
        $this->headers->addTextHeader('X-Foo');
        $this->assertStringNotContainsString('X-Foo', $this->headers->toString());
        $this->assertInstanceOf(HeaderSet::class, $this->headers->setAlwaysDisplayed('X-Foo'));
        $this->assertStringContainsString('X-Foo', $this->headers->toString());
    }

    public function test_toString()
    {
        $this->assertStringContainsString('X-Foo: bar', $this->headers->add('X-Foo', 'bar')->toString());
    }

    public function test___toString()
    {
        $this->assertStringContainsString('X-Foo: bar', (string)$this->headers->add('X-Foo', 'bar'));
    }

    /**
     * @covers Rebet\Mail\Mime\HeaderSet::toReadableString
     * @covers Rebet\Mail\Mime\HeaderSet::convertToReadableString
     */
    public function test_toReadableString()
    {
        $headers = Mail::text()->headers();
        $headers->addTextHeader('Subject', "テスト");
        $headers->addMailboxHeader('To', ['to@foo.com' => '宛先']);
        $headers->addParameterizedHeader('X-Parameterized', 'パラメータ', ['filename' => 'ファイル名']);
        $this->assertContainsString(
            [
                'Subject: テスト',
                'To: 宛先 <to@foo.com>',
                'X-Parameterized: パラメータ; filename="ファイル名"',
            ],
            $headers->toReadableString()
        );
    }

    public function test_toSwiftHeaderSet()
    {
        $this->assertInstanceOf(Swift_Mime_SimpleHeaderSet::class, $this->headers->toSwiftHeaderSet());
    }
}
