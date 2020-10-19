<?php
namespace Rebet\Tests\Mail\Mime;

use Rebet\Mail\Mime\MimePart;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Template\Text;
use Swift_ByteStream_ArrayByteStream;

class MimePartTest extends RebetTestCase
{
    public function test_getBody()
    {
        $body = Text::of('This is rendrable body - {{$value}}')->with(['value' => 'foo①']);
        $part = new MimePart($body);
        $this->assertSame("This is rendrable body - foo①", $part->getBody());
        $body->with(['value' => 'bar①']);
        $this->assertSame("This is rendrable body - bar①", $part->getBody());

        $part = new MimePart("string①");
        $this->assertSame("string①", $part->getBody());

        $part = new MimePart(new Swift_ByteStream_ArrayByteStream(['foo', 'bar', 'baz', '①']));
        $this->assertSame("foobarbaz①", $part->getBody());
    }

    public function test_getBody_withEncodeCharset()
    {
        $body = Text::of('This is rendrable body - {{$value}}')->with(['value' => 'foo①']);
        $part = new MimePart($body, null, 'iso-2022-jp');
        $this->assertSame("This is rendrable body - foo?", $part->getBody());
        $body->with(['value' => 'bar①']);
        $this->assertSame("This is rendrable body - bar?", $part->getBody());

        $part = new MimePart("string①", null, 'iso-2022-jp');
        $this->assertSame("string?", $part->getBody());

        $part = new MimePart(new Swift_ByteStream_ArrayByteStream(['foo', 'bar', 'baz', '①']), null, 'iso-2022-jp');
        $this->assertSame("foobarbaz?", $part->getBody());
    }
}
