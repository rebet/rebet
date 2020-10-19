<?php
namespace Rebet\Tests\Mail;

use Rebet\Mail\Mail;
use Rebet\Mail\Mailer;
use Rebet\Mail\Plugins\AlwaysBccPlugin;
use Rebet\Mail\Transport\ArrayTransport;
use Rebet\Tests\RebetTestCase;

class MailerTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(Mailer::class, new Mailer(new ArrayTransport()));
    }

    public function test_send()
    {
        $mailer = new Mailer(new ArrayTransport());
        $this->assertSame([], $mailer->send($mail = Mail::text()));
        $this->assertSame([$mail->message()], $mailer->transport()->messages());
    }

    public function test_plugin()
    {
        $mailer = new Mailer(new ArrayTransport());
        $mailer->plugin(new AlwaysBccPlugin('bcc@foo.com'));
        $this->assertSame([], $mailer->send($mail = Mail::text()));
        $this->assertSame(['bcc@foo.com' => null], $mail->bcc());
    }

    public function test_transport()
    {
        $mailer = new Mailer($transport = new ArrayTransport());
        $this->assertInstanceOf(ArrayTransport::class, $mailer->transport());
        $this->assertSame($transport, $mailer->transport());
    }
}
