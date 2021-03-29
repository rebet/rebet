<?php
namespace Rebet\Tests\Mail\Mime;

use Rebet\Mail\Mime\MimeMessage;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Template\Letterpress;
use Swift_ByteStream_ArrayByteStream;

class MimeMessageTest extends RebetTestCase
{
    public function test_getBody()
    {
        $message = new MimeMessage();
        $body    = Letterpress::of('This is rendrable body - {{$value}}')->with(['value' => 'foo①']);
        $message->setBody($body);
        $this->assertSame("This is rendrable body - foo①", $message->getBody());
        $body->with(['value' => 'bar①']);
        $this->assertSame("This is rendrable body - bar①", $message->getBody());

        $message = new MimeMessage(null, "string①");
        $this->assertSame("string①", $message->getBody());

        $message = new MimeMessage(null, new Swift_ByteStream_ArrayByteStream(['foo', 'bar', 'baz', '①']));
        $this->assertSame("foobarbaz①", $message->getBody());
    }

    public function test_getBody_withEncodeCharset()
    {
        $message = new MimeMessage();
        $body    = Letterpress::of('This is rendrable body - {{$value}}')->with(['value' => 'foo①']);
        $message->setBody($body, null, 'iso-2022-jp');
        $this->assertSame("This is rendrable body - foo?", $message->getBody());
        $body->with(['value' => 'bar①']);
        $this->assertSame("This is rendrable body - bar?", $message->getBody());

        $message = new MimeMessage(null, "string①", null, 'iso-2022-jp');
        $this->assertSame("string?", $message->getBody());

        $message = new MimeMessage(null, new Swift_ByteStream_ArrayByteStream(['foo', 'bar', 'baz', '①']), null, 'iso-2022-jp');
        $this->assertSame("foobarbaz?", $message->getBody());
    }

    public function test_addPart()
    {
        $message = new MimeMessage();
        $body    = Letterpress::of('This is rendrable body - {{$value}}')->with(['value' => 'foo①']);
        $message->addPart($body);
        $this->assertSame("This is rendrable body - foo①", $message->getPart()->getBody());
        $body->with(['value' => 'bar①']);
        $this->assertSame("This is rendrable body - bar①", $message->getPart()->getBody());
    }

    public function test_addPart_withEncodeCharset()
    {
        $message = new MimeMessage();
        $body    = Letterpress::of('This is rendrable body - {{$value}}')->with(['value' => 'foo①']);
        $message->addPart($body, null, 'iso-2022-jp');
        $this->assertSame("This is rendrable body - foo?", $message->getPart()->getBody());
        $body->with(['value' => 'bar①']);
        $this->assertSame("This is rendrable body - bar?", $message->getPart()->getBody());
    }

    public function test_getPart()
    {
        $message = new MimeMessage();
        $message->addPart('0');
        $message->addPart('1');
        $message->addPart('2');
        $this->assertSame("0", $message->getPart()->getBody());
        $this->assertSame("2", $message->getPart(2)->getBody());
        $this->assertSame(null, $message->getPart(9));
    }

    /**
     * @covers Rebet\Mail\Mime\MimeMessage::toReadableString
     * @covers Rebet\Mail\Mime\MimeMessage::convertToReadableString
     */
    public function test_toReadableString()
    {
        $message = new MimeMessage();
        $message->setSubject("テスト");
        $message->setTo(['to@foo.com' => '宛先']);
        $message->setFrom(['from@foo.com' => '送り元']);
        $message->setBody('<html><head></head><body><h1>件名</h1><p>本文</p></body></html>', 'text/html');
        $message->addPart("# 件名\n本文", 'text/plain');
        $this->assertStringContainsAll(
            [
                'Subject: テスト',
                'From: 送り元 <from@foo.com>',
                'To: 宛先 <to@foo.com>',
                '<html><head></head><body><h1>件名</h1><p>本文</p></body></html>',
                "# 件名\n本文",
            ],
            $message->toReadableString()
        );
    }
}
