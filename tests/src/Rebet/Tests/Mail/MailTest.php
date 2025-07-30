<?php
namespace Rebet\Tests\Mail;

use Rebet\Application\App;
use Rebet\Log\Log;
use Rebet\Mail\Mail;
use Rebet\Mail\Mailer;
use Rebet\Mail\Mime\HeaderSet;
use Rebet\Mail\Mime\MimeMessage;
use Rebet\Mail\Mime\MimePart;
use Rebet\Mail\Transport\ArrayTransport;
use Rebet\Mail\Transport\LogTransport;
use Rebet\Mail\Transport\SendmailTransport;
use Rebet\Mail\Transport\SmtpTransport;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Strings;
use Swift_DependencyContainer;
use Swift_Mime_ContentEncoder_Base64ContentEncoder;
use Swift_Mime_ContentEncoder_PlainContentEncoder;
use Swift_Mime_ContentEncoder_QpContentEncoder;
use Swift_Mime_ContentEncoder_QpContentEncoderProxy;

class MailTest extends RebetTestCase
{
    public function test_clear()
    {
        $mailer = Mail::mailer('test');
        $this->assertSame(true, $this->inspect(Mail::class, 'initialized'));
        $this->assertSame(['test' => $mailer], $this->inspect(Mail::class, 'mailers'));
        Mail::clear();
        $this->assertSame(false, $this->inspect(Mail::class, 'initialized'));
        $this->assertSame([], $this->inspect(Mail::class, 'mailers'));
    }

    public function test_development()
    {
        $this->assertSame(true, Mail::development(true));
        $this->assertSame(true, Mail::development());
        $this->assertSame(false, Mail::development(false));
        $this->assertSame(false, Mail::development());
    }

    public function test_unittest()
    {
        $this->assertSame(true, Mail::unittest(true));
        $this->assertSame(true, Mail::unittest());
        $this->assertSame(false, Mail::unittest(false));
        $this->assertSame(false, Mail::unittest());
    }

    public function test_mailer()
    {
        $this->assertSame(false, $this->inspect(Mail::class, 'initialized'));
        $this->assertInstanceOf(Mailer::class, Mail::mailer());
        $this->assertSame(true, $this->inspect(Mail::class, 'initialized'));

        Mail::development(false);
        Mail::unittest(false);
        $this->assertInstanceOf(SmtpTransport::class, Mail::mailer()->transport());
        $this->assertInstanceOf(SendmailTransport::class, Mail::mailer('sendmail')->transport());

        Mail::development(true);
        $this->assertInstanceOf(LogTransport::class, Mail::mailer()->transport());
        $this->assertInstanceOf(LogTransport::class, Mail::mailer('sendmail')->transport());

        Mail::unittest(true);
        $this->assertInstanceOf(ArrayTransport::class, Mail::mailer()->transport());
        $this->assertInstanceOf(ArrayTransport::class, Mail::mailer('sendmail')->transport());
    }

    public function test_container()
    {
        Config::application([
            Mail::class => [
                'initialize' => [
                    'handler' => function (Swift_DependencyContainer $c) { $c->register('test.value')->asValue('test'); }
                ]
            ],
        ]);
        $this->assertSame(false, $this->inspect(Mail::class, 'initialized'));
        $this->assertInstanceOf(Swift_DependencyContainer::class, $c = Mail::container());
        $this->assertSame(true, $this->inspect(Mail::class, 'initialized'));

        $this->assertSame('UTF-8', $c->lookup('properties.charset'));
        $this->assertSame('test', $c->lookup('test.value'));
    }

    public function test_generatorAndGenerateAlternativePart()
    {
        $mail = Mail::html()
            ->body('<html><head></head><body><h1>Subject</h1><div>Text <b>bold</b> <i>italic</i></div></body></html>')
            ->generateAlternativePart();
        $this->assertStringContainsString("SUBJECT\n\nText BOLD _italic_", $mail->toReadableString());

        Mail::generator('text/html', 'text/plain', function ($body, $options = []) { return strip_tags(str_replace('</h1>', "\n----------\n\n", $body)); });

        $mail = Mail::html()
            ->body('<html><head></head><body><h1>Subject</h1><div>Text <b>bold</b> <i>italic</i></div></body></html>')
            ->generateAlternativePart();
        $this->assertStringContainsString("Subject\n----------\n\nText bold italic", $mail->toReadableString());
    }

    public function test_text()
    {
        $this->assertSame(false, $this->inspect(Mail::class, 'initialized'));
        $this->assertInstanceOf(Mail::class, $mail = Mail::text());
        $this->assertSame(true, $this->inspect(Mail::class, 'initialized'));
        $this->assertSame('text/plain', $mail->contentType());
    }

    public function test_html()
    {
        $this->assertSame(false, $this->inspect(Mail::class, 'initialized'));
        $this->assertInstanceOf(Mail::class, $mail = Mail::html());
        $this->assertSame(true, $this->inspect(Mail::class, 'initialized'));
        $this->assertSame('text/html', $mail->contentType());
    }

    public function test___construct()
    {
        $this->assertSame(false, $this->inspect(Mail::class, 'initialized'));
        $this->assertInstanceOf(Mail::class, new Mail());
        $this->assertSame(true, $this->inspect(Mail::class, 'initialized'));
    }

    public function dataResolves() : array
    {
        return [
            [[], null],
            [[], []],
            [['one@foo.com' => null], 'one@foo.com'],
            [['one@foo.com' => 'One'], 'One<one@foo.com>'],
            [['one@foo.com' => 'One'], 'One <one@foo.com>'],
            [['one@foo.com' => 'One'], 'One  <one@foo.com>'],
            [['one@foo.com' => 'One'], ' One  <one@foo.com>'],
            [['one@foo.com' => 'O n e'], 'O n e <one@foo.com>'],
            [['one@foo.com' => 'One'], ['one@foo.com' => 'One']],
            [['one@foo.com' => 'One', 'two@foo.com' => null], ['one@foo.com' => 'One', 'two@foo.com']],
            [['one@foo.com' => 'One', 'two@foo.com' => null, 'three@foo.com' => 'Three'], ['one@foo.com' => 'One', 'two@foo.com', 'Three <three@foo.com>']],
            [['mb@foo.com' => 'マルチ バイト'], 'マルチ バイト <mb@foo.com>'],
        ];
    }

    /**
     * @dataProvider dataResolves
     */
    public function test_resolve($expect, $value)
    {
        $this->assertSame($expect, Mail::resolve($value));
    }

    public function test_from()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->from());
        $this->assertInstanceOf(Mail::class, $mail->from('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->from());

        $this->assertInstanceOf(Mail::class, $mail->from(['two@foo.com', 'Three <three@foo.com>']));
        $this->assertSame(['two@foo.com' => null, 'three@foo.com' => 'Three'], $mail->from());
    }

    public function test_sender()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->sender());
        $this->assertInstanceOf(Mail::class, $mail->sender('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->sender());
    }

    public function test_returnPath()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->returnPath());
        $this->assertInstanceOf(Mail::class, $mail->returnPath('One <one@foo.com>'));
        $this->assertSame('one@foo.com', $mail->returnPath());
    }

    public function test_to()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->to());
        $this->assertInstanceOf(Mail::class, $mail->to('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->to());

        $this->assertInstanceOf(Mail::class, $mail->to(['two@foo.com', 'Three <three@foo.com>']));
        $this->assertSame(['two@foo.com' => null, 'three@foo.com' => 'Three'], $mail->to());
    }

    public function test_cc()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->cc());
        $this->assertInstanceOf(Mail::class, $mail->cc('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->cc());

        $this->assertInstanceOf(Mail::class, $mail->cc(['two@foo.com', 'Three <three@foo.com>']));
        $this->assertSame(['two@foo.com' => null, 'three@foo.com' => 'Three'], $mail->cc());
    }

    public function test_bcc()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->bcc());
        $this->assertInstanceOf(Mail::class, $mail->bcc('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->bcc());

        $this->assertInstanceOf(Mail::class, $mail->bcc(['two@foo.com', 'Three <three@foo.com>']));
        $this->assertSame(['two@foo.com' => null, 'three@foo.com' => 'Three'], $mail->bcc());
    }

    public function test_replyTo()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->replyTo());
        $this->assertInstanceOf(Mail::class, $mail->replyTo('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->replyTo());

        $this->assertInstanceOf(Mail::class, $mail->replyTo(['two@foo.com', 'Three <three@foo.com>']));
        $this->assertSame(['two@foo.com' => null, 'three@foo.com' => 'Three'], $mail->replyTo());
    }

    public function test_dispositionNotificationTo()
    {
        $mail = new Mail();
        $this->assertSame([], $mail->dispositionNotificationTo());
        $this->assertInstanceOf(Mail::class, $mail->dispositionNotificationTo('One <one@foo.com>'));
        $this->assertSame(['one@foo.com' => 'One'], $mail->dispositionNotificationTo());

        $this->assertInstanceOf(Mail::class, $mail->dispositionNotificationTo(['two@foo.com', 'Three <three@foo.com>']));
        $this->assertSame(['two@foo.com' => null, 'three@foo.com' => 'Three'], $mail->dispositionNotificationTo());
    }

    public function test_subject()
    {
        $mail = new Mail();
        $this->assertSame(null, $mail->subject());
        $this->assertInstanceOf(Mail::class, $mail->subject('Subject 件名'));
        $this->assertSame('Subject 件名', $mail->subject());
    }

    public function test_contentType()
    {
        $mail = new Mail();
        $this->assertSame('text/plain', $mail->contentType());
        $this->assertInstanceOf(Mail::class, $mail->contentType('text/html'));
        $this->assertSame('text/html', $mail->contentType());
        $this->assertSame("Content-Type: text/html; charset=UTF-8\r\n", $mail->headers()->get('Content-Type')->toString());
    }

    public function test_charset()
    {
        $mail = new Mail();
        $this->assertSame('UTF-8', $mail->charset());
        $this->assertInstanceOf(Mail::class, $mail->charset('is-2022-jp'));
        $this->assertSame('is-2022-jp', $mail->charset());
        $this->assertSame("Content-Type: text/plain; charset=is-2022-jp\r\n", $mail->headers()->get('Content-Type')->toString());
    }

    public function test_format()
    {
        $mail = new Mail();
        $this->assertSame(null, $mail->format());
        $this->assertInstanceOf(Mail::class, $mail->format('flowed'));
        $this->assertSame('flowed', $mail->format());
        $this->assertSame("Content-Type: text/plain; charset=UTF-8; format=flowed\r\n", $mail->headers()->get('Content-Type')->toString());
    }

    public function test_delsp()
    {
        $mail = new Mail();
        $this->assertSame(false, $mail->delsp());
        $this->assertInstanceOf(Mail::class, $mail->delsp(true));
        $this->assertSame(true, $mail->delsp());
        $this->assertSame("Content-Type: text/plain; charset=UTF-8; delsp=yes\r\n", $mail->headers()->get('Content-Type')->toString());
    }

    public function test_encoder()
    {
        $mail = new Mail();
        $this->assertInstanceOf(Swift_Mime_ContentEncoder_Base64ContentEncoder::class, $mail->encoder());
        $this->assertSame("Content-Transfer-Encoding: base64\r\n", $mail->headers()->get('Content-Transfer-Encoding')->toString());

        $this->assertInstanceOf(Mail::class, $mail->encoder('quoted-printable'));
        $this->assertInstanceOf(Swift_Mime_ContentEncoder_QpContentEncoderProxy::class, $mail->encoder());
        $this->assertSame("Content-Transfer-Encoding: quoted-printable\r\n", $mail->headers()->get('Content-Transfer-Encoding')->toString());

        $this->assertInstanceOf(Mail::class, $mail->encoder('7bit'));
        $this->assertInstanceOf(Swift_Mime_ContentEncoder_PlainContentEncoder::class, $mail->encoder());
        $this->assertSame("Content-Transfer-Encoding: 7bit\r\n", $mail->headers()->get('Content-Transfer-Encoding')->toString());

        $this->assertInstanceOf(Mail::class, $mail->encoder('8bit'));
        $this->assertInstanceOf(Swift_Mime_ContentEncoder_PlainContentEncoder::class, $mail->encoder());
        $this->assertSame("Content-Transfer-Encoding: 8bit\r\n", $mail->headers()->get('Content-Transfer-Encoding')->toString());

        $this->assertInstanceOf(Mail::class, $mail->encoder('base64'));
        $this->assertInstanceOf(Swift_Mime_ContentEncoder_Base64ContentEncoder::class, $mail->encoder());
        $this->assertSame("Content-Transfer-Encoding: base64\r\n", $mail->headers()->get('Content-Transfer-Encoding')->toString());

        $this->assertInstanceOf(Mail::class, $mail->encoder('safeqp'));
        $this->assertInstanceOf(Swift_Mime_ContentEncoder_QpContentEncoder::class, $mail->encoder());
        $this->assertSame("Content-Transfer-Encoding: quoted-printable\r\n", $mail->headers()->get('Content-Transfer-Encoding')->toString());
    }

    public function test_priority()
    {
        $mail = new Mail();
        $this->assertSame(3, $mail->priority());
        $this->assertInstanceOf(Mail::class, $mail->priority(4));
        $this->assertSame(4, $mail->priority());
        $this->assertSame("X-Priority: 4 (Low)\r\n", $mail->headers()->get('X-Priority')->toString());
    }

    public function test_attach()
    {
        $mail = new Mail();
        $this->assertInstanceOf(Mail::class, $mail->attach(App::structure()->public('/assets/img/72x72.png'), 'ファイル名.png'));
        $headers = $mail->message()->getPart()->getHeaders();
        $this->assertSame("Content-Type: image/png; name=\"=?UTF-8?B?44OV44Kh44Kk44Or5ZCNLnBuZw==?=\"\r\n", $headers->get('Content-Type')->toString());
        $this->assertStringContainsString("attachment", $headers->get('Content-Disposition')->toString());
    }

    public function test_attachData()
    {
        $mail = new Mail();
        $this->assertInstanceOf(Mail::class, $mail->attachData('本文', 'ファイル名.txt'));
        $headers = $mail->message()->getPart()->getHeaders();
        $this->assertSame("Content-Type: application/octet-stream;\r\n name=\"=?UTF-8?B?44OV44Kh44Kk44Or5ZCNLnR4dA==?=\"\r\n", $headers->get('Content-Type')->toString());
        $this->assertStringContainsString("attachment", $headers->get('Content-Disposition')->toString());
    }

    public function test_embedAndCid()
    {
        $mail = new Mail();
        $this->assertSame(null, $mail->cid('ファイル名.png'));
        $this->assertStringStartsWith('cid:', $cid = $mail->embed(App::structure()->public('/assets/img/72x72.png'), 'ファイル名.png'));
        $headers = $mail->message()->getPart()->getHeaders();
        $this->assertSame($cid, $mail->cid('ファイル名.png'));
        $this->assertSame("Content-Type: image/png; name=\"=?UTF-8?B?44OV44Kh44Kk44Or5ZCNLnBuZw==?=\"\r\n", $headers->get('Content-Type')->toString());
        $this->assertSame("Content-ID: <".Strings::ltrim($cid, 'cid:').">\r\n", $headers->get('Content-ID')->toString());
        $this->assertStringContainsString("inline", $headers->get('Content-Disposition')->toString());

        $this->assertSame(null, $mail->cid('72x72.png'));
        $this->assertStringStartsWith('cid:', $cid = $mail->embed(App::structure()->public('/assets/img/72x72.png')));
        $this->assertSame($cid, $mail->cid('72x72.png'));
    }

    public function test_embedDataAndCid()
    {
        $mail = new Mail();
        $this->assertSame(null, $mail->cid('ファイル名.txt'));
        $this->assertStringStartsWith('cid:', $cid = $mail->embedData('本文', 'ファイル名.txt'));
        $headers = $mail->message()->getPart()->getHeaders();
        $this->assertSame($cid, $mail->cid('ファイル名.txt'));
        $this->assertSame("Content-Type: application/octet-stream;\r\n name=\"=?UTF-8?B?44OV44Kh44Kk44Or5ZCNLnR4dA==?=\"\r\n", $headers->get('Content-Type')->toString());
        $this->assertSame("Content-ID: <".Strings::ltrim($cid, 'cid:').">\r\n", $headers->get('Content-ID')->toString());
        $this->assertStringContainsString("inline", $headers->get('Content-Disposition')->toString());
    }

    public function test_date()
    {
        $mail = new Mail();
        $this->assertInstanceOf(DateTime::class, $old = $mail->date());
        $this->assertInstanceOf(Mail::class, $mail->date($now = DateTime::now()));
        $this->assertEquals($now, $mail->date());
        $this->assertNotEquals($old, $now);
        $this->assertSame("Date: ".$now->format(DateTime::RFC2822)."\r\n", $mail->headers()->get('Date')->toString());
    }

    public function test_body()
    {
        $mail = Mail::text();
        $this->assertInstanceOf(Mail::class, $mail->body('Body'));
        $this->assertSame("Content-Type: text/plain; charset=UTF-8\r\n", $mail->headers()->get('Content-Type')->toString());

        $mail = Mail::html();
        $this->assertInstanceOf(Mail::class, $mail->body('Body'));
        $this->assertSame("Content-Type: text/html; charset=UTF-8\r\n", $mail->headers()->get('Content-Type')->toString());

        $mail = Mail::html();
        $this->assertInstanceOf(Mail::class, $mail->body('Body', 'text/plain', 'iso-2022-jp'));
        $this->assertSame("Content-Type: text/plain; charset=iso-2022-jp\r\n", $mail->headers()->get('Content-Type')->toString());
    }

    public function test_part()
    {
        $mail = Mail::text();
        $this->assertSame(null, $mail->part());
        $this->assertInstanceOf(Mail::class, $mail->part('Part', 'text/plain', 'iso-2022-jp'));
        $part = $mail->part();
        $this->assertInstanceOf(MimePart::class, $part);
        $this->assertSame('Part', $part->getBody());
        $this->assertSame("Content-Type: text/plain; charset=iso-2022-jp\r\n", $part->getHeaders()->get('Content-Type')->toString());
    }

    public function test_id()
    {
        $mail = new Mail();
        $this->assertStringEndsWith('@rebet.generated', $old = $mail->id());
        $this->assertInstanceOf(Mail::class, $mail->id('newid@domain.of.yours'));
        $this->assertNotEquals($old, 'newid@domain.of.yours');
        $this->assertSame("Message-ID: <newid@domain.of.yours>\r\n", $mail->headers()->get('Message-ID')->toString());

        Mail::clear();
        Config::application([
            Mail::class => [
                'initialize' => [
                    'default' => [
                        'idright' => 'domain.of.yours'
                    ]
                ]
            ]
        ]);

        $mail = new Mail();
        $this->assertStringEndsWith('@domain.of.yours', $mail->id());
    }

    public function test_headers()
    {
        $this->assertInstanceOf(HeaderSet::class, Mail::text()->headers());
    }

    public function test_toString()
    {
        $this->assertStringContainsAll(
            [
                'Subject: Title',
                "To: =?UTF-8?B?5a6b5YWI?= <to@foo.com>",
                'Qk9EWQ==',
                'PGI+Ym9keTwvYj4='
            ],
            Mail::html()->subject('Title')->body('<b>body</b>')->generateAlternativePart()->to('宛先 <to@foo.com>')->toString()
        );
    }

    public function test_toReadableString()
    {
        $this->assertStringContainsAll(
            [
                'Subject: Title',
                "To: 宛先 <to@foo.com>",
                'BODY',
                '<b>body</b>'
            ],
            Mail::html()->subject('Title')->body('<b>body</b>')->generateAlternativePart()->to('宛先 <to@foo.com>')->toReadableString()
        );
    }

    public function test_message()
    {
        $this->assertInstanceOf(MimeMessage::class, Mail::text()->message());
    }

    public function test_send()
    {
        $mail = Mail::html()->subject('Title')->body('<b>body</b>')->generateAlternativePart()->to('宛先 <to@test.local>');
        $this->assertSame([], $mail->send());
        $this->assertEquals([$mail->message()], Mail::mailer()->transport()->messages());
    }
}
