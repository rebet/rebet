<?php
namespace Rebet\Tests\Mail;

use Rebet\Mail\Email;
use Rebet\Mail\Transport\FailoverTransport;
use Rebet\Mail\Transport\InMemoryTransport;
use Rebet\Mail\Transport\RoundRobinTransport;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Rebet\Tools\DateTime\DateTime;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Exception\LogicException;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Mime\Part\Multipart\RelatedPart;
use Symfony\Component\Mime\Part\TextPart;

class EmailTest extends RebetTestCase
{
    public function test_mailer()
    {
        $mailer = Email::mailer('unittest');
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertInstanceOf(InMemoryTransport::class, $this->inspect($mailer, 'transport'));

        $mailer = Email::mailer('main');
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertInstanceOf(NullTransport::class, $this->inspect($mailer, 'transport'));

        Email::reset();
        $mailer = Email::mailer(); // default_mailer is 'unittest' configured in tests/app/core/configs/mail.php
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertInstanceOf(InMemoryTransport::class, $this->inspect($mailer, 'transport'));

        Email::reset();
        Config::runtime([
            Email::class => [
                'mailers' => [
                    'rotate' => [
                        'transport' => [
                            '@factory'   => RoundRobinTransport::class,
                            'transports' => [
                                ['@factory' => Transport::class."::fromDsn", 'dsn' => 'sendmail://default'],
                                ['@factory' => InMemoryTransport::class],
                                ['@factory' => NullTransport::class],
                            ],
                            'retry_period' => 60,
                            'logger'       => null,
                        ],
                        'bus'        => null,
                        'dispatcher' => null,
                    ],
                ],
            ],
        ]);

        $mailer = Email::mailer('rotate');
        $this->assertInstanceOf(Mailer::class, $mailer);
        $transport = $this->inspect($mailer, 'transport');
        $this->assertInstanceOf(RoundRobinTransport::class, $transport);
        $transports = $this->inspect($transport, 'transports');
        $this->assertCount(3, $transports);
        $this->assertInstanceOf(SendmailTransport::class, $transports[0]);
        $this->assertInstanceOf(InMemoryTransport::class, $transports[1]);
        $this->assertInstanceOf(NullTransport::class, $transports[2]);

        Email::reset();
        Config::runtime([
            Email::class => [
                'mailers' => [
                    'failover' => [
                        'transport' => [
                            '@factory'   => FailoverTransport::class,
                            'transports' => [
                                ['@factory' => Transport::class."::fromDsn", 'dsn' => 'sendmail://default'],
                                InMemoryTransport::class,
                                NullTransport::class,
                            ],
                            'retry_period' => 60,
                            'logger'       => null,
                        ],
                        'bus'        => null,
                        'dispatcher' => null,
                    ],
                ],
            ],
        ]);

        $mailer = Email::mailer('failover');
        $this->assertInstanceOf(Mailer::class, $mailer);
        $transport = $this->inspect($mailer, 'transport');
        $this->assertInstanceOf(FailoverTransport::class, $transport);
        $transports = $this->inspect($transport, 'transports');
        $this->assertCount(3, $transports);
        $this->assertInstanceOf(SendmailTransport::class, $transports[0]);
        $this->assertInstanceOf(InMemoryTransport::class, $transports[1]);
        $this->assertInstanceOf(NullTransport::class, $transports[2]);
    }

    public function test_mailer_not_configured()
    {
        $this->expectException(ConfigNotDefineException::class);
        Email::mailer('non_existent');
    }

    public function test_transport()
    {
        $transport = Email::transport('unittest');
        $this->assertInstanceOf(TransportInterface::class, $transport);
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
    }

    public function test_transport_not_configured()
    {
        $this->expectException(ConfigNotDefineException::class);
        Email::transport('non_existent');
    }

    public function test_reset()
    {
        $mailer = Email::mailer('unittest');
        $this->assertSame(['unittest' => $mailer], $this->inspect(Email::class, 'mailers'));
        Email::reset();
        $this->assertSame([], $this->inspect(Email::class, 'mailers'));
    }

    public function test_generateTextBodyFromHtml()
    {
        $html = '<html><head></head><body><h1>Subject</h1><div>Text <b>bold</b> <i>italic</i></div></body></html>';
        $mail = (new Email())
            ->html($html)
            ->generateTextBodyFromHtml()
        ;
        $this->assertEquals($html, $mail->getHtmlBody());
        $this->assertStringContainsString("SUBJECT\n\nText BOLD _italic_", $mail->getTextBody());

        $mail = (new Email())
            ->html($html)
            ->generateTextBodyFromHtml(fn ($body) => strip_tags(str_replace('</h1>', "\n----------\n\n", $body)))
        ;
        $this->assertEquals($html, $mail->getHtmlBody());
        $this->assertStringContainsString("Subject\n----------\n\nText bold italic", $mail->getTextBody());
    }

    public function test_build()
    {
        Config::runtime([
            DateTime::class => [
                'default_timezone' => 'Asia/Tokyo',
            ]
        ]);
        DateTime::setTestNow('2012-01-23 12:34:56', 'Asia/Tokyo');

        $mail = (new Email())
            ->subject('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる')
            ->from('差出人 <from@test.local>')
            ->to('宛先 <to@test.local>', 'Tom <tom@test.local>')
            ->cc('長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる <cc@test.local>', '宛先2 <cc2@test.local>', 'CC3 <cc3@test.local>')
            ->bcc('隠しBCC <bcc@test.local>', 'BCC2 <bcc2@test.local>')
            ->replyTo('返信先 <reply@test.local>')
            ->html('<b>本文</b>')
            ->generateTextBodyFromHtml()
        ;
        $this->assertEquals(null, $mail->getDate());

        $builded_mail = $mail->build();

        $this->assertNotEquals($mail, $builded_mail);

        $this->assertInstanceOf(Email::class, $mail);
        $this->assertInstanceOf(Email::class, $builded_mail);

        $this->assertEquals('Asia/Tokyo', $mail->getDate()->getTimezone()->getName());
        $this->assertEquals('Asia/Tokyo', $builded_mail->getDate()->getTimezone()->getName());
        $this->assertEquals('2012-01-23 12:34:56', $mail->getDate()->format('Y-m-d H:i:s'));
        $this->assertEquals('2012-01-23 12:34:56', $builded_mail->getDate()->format('Y-m-d H:i:s'));

        $this->assertEquals('<b>本文</b>', $mail->getHtmlBody());
        $this->assertEquals('<b>本文</b>', $builded_mail->getHtmlBody());
        $this->assertStringContainsString("本文", $mail->getTextBody());
        $this->assertStringContainsString("本文", $builded_mail->getTextBody());

        // The original Email must be left untouched; build() only encodes headers on the clone.
        $from_header = $mail->getHeaders()->get('From');
        $this->assertInstanceOf(MailboxListHeader::class, $from_header);
        $this->assertSame('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる', $mail->getHeaders()->get('Subject')->getBody());
        $this->assertSame('差出人', $from_header->getAddresses()[0]->getName());

        $subject_header = $builded_mail->getHeaders()->get('Subject')->getBodyAsString();
        $this->assertStringStartsWith('=?UTF-8?B?', $subject_header);
        $this->assertStringNotContainsString('=?UTF-8?B??=', $subject_header, 'No empty encoded-word should ever be produced');
        $this->assertGreaterThan(1, substr_count($subject_header, '=?UTF-8?B?'), 'Long subject should be split into multiple encoded-words');
        $this->assertSame('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる', mb_decode_mimeheader($subject_header));

        $from_header = $builded_mail->getHeaders()->get('From')->getBodyAsString();
        $this->assertSame('差出人 <from@test.local>', mb_decode_mimeheader($from_header));

        $to_header = $builded_mail->getHeaders()->get('To')->getBodyAsString();
        $this->assertStringContainsString('Tom <tom@test.local>', $to_header, 'A pure ASCII display name must be left as-is, not wrapped in an encoded-word');
        $this->assertSame('宛先 <to@test.local>, Tom <tom@test.local>', mb_decode_mimeheader($to_header));

        $cc_header = $builded_mail->getHeaders()->get('Cc')->getBodyAsString();
        $this->assertStringNotContainsString('=?UTF-8?B??=', $cc_header, 'No empty encoded-word should ever be produced');
        $this->assertGreaterThan(1, substr_count($cc_header, '=?UTF-8?B?'), 'Long Cc display name should be split into multiple encoded-words');
        $this->assertStringContainsString('CC3 <cc3@test.local>', $cc_header, 'A pure ASCII display name must be left as-is, not wrapped in an encoded-word');
        $this->assertSame(
            '長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる <cc@test.local>, 宛先2 <cc2@test.local>, CC3 <cc3@test.local>',
            mb_decode_mimeheader($cc_header)
        );

        $bcc_header = $builded_mail->getHeaders()->get('Bcc')->getBodyAsString();
        $this->assertStringNotContainsString('=?UTF-8?B??=', $bcc_header, 'No empty encoded-word should ever be produced');
        $this->assertStringContainsString('BCC2 <bcc2@test.local>', $bcc_header, 'A pure ASCII display name must be left as-is, not wrapped in an encoded-word');
        $this->assertSame('隠しBCC <bcc@test.local>, BCC2 <bcc2@test.local>', mb_decode_mimeheader($bcc_header));

        $reply_to_header = $builded_mail->getHeaders()->get('Reply-To')->getBodyAsString();
        $this->assertStringStartsWith('=?UTF-8?B?', $reply_to_header);
        $this->assertSame('返信先 <reply@test.local>', mb_decode_mimeheader($reply_to_header));
    }

    /**
     * Regression test for a bug where Base64-encoding a short multi-byte Subject value (one
     * that fits entirely within a single encoded-word, e.g. "タイトル") produced a spurious,
     * empty second line/encoded-word, e.g.:
     *   "Subject: =?utf-8?B?44K/44Kk44OI44Or?=\r\n =?utf-8?B??=\r\n"
     * even though the whole value fit well within the 76 character line limit.
     */
    public function test_build_withShortMultibyteSubject()
    {
        $mail = (new Email())
            ->subject('タイトル')
            ->from('from@test.local')
            ->to('to@test.local')
            ->text('text body')
        ;

        $builded_mail = $mail->build();

        $subject_header = $builded_mail->getHeaders()->get('Subject');
        $body_as_string = $subject_header->getBodyAsString();

        $this->assertSame('=?UTF-8?B?'.base64_encode('タイトル').'?=', $body_as_string);
        $this->assertStringNotContainsString("\r\n", $body_as_string, 'No line-fold should be inserted when the whole subject fits on a single line');
        $this->assertSame(1, substr_count($body_as_string, '=?UTF-8?B?'), 'A short subject must not be split into multiple (or spurious empty) encoded-words');
        $this->assertSame('タイトル', mb_decode_mimeheader($body_as_string));

        // Also verify the fully rendered header line (as it would actually be sent over the
        // wire) has no spurious blank continuation line.
        $rendered = $subject_header->toString();
        $this->assertSame('Subject: =?UTF-8?B?'.base64_encode('タイトル').'?=', $rendered);
        $this->assertStringNotContainsString("\r\n", $rendered);
    }

    public function test_build_withQuotedPrintableHeaderEncoder()
    {
        Config::runtime([
            Email::class => [
                'encodes' => [
                    'header' => 'quoted-printable',
                ],
            ],
        ]);

        $builded_mail = (new Email())
            ->subject('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる')
            ->from('差出人 <from@test.local>')
            ->html('<b>本文</b>')
            ->build()
        ;

        $subject_header = $builded_mail->getHeaders()->get('Subject')->getBodyAsString();
        $this->assertStringStartsWith('=?UTF-8?Q?', $subject_header);
        $this->assertStringNotContainsString('=?UTF-8?Q??=', $subject_header, 'No empty encoded-word should ever be produced');
        $this->assertGreaterThan(1, substr_count($subject_header, '=?UTF-8?Q?'), 'Long subject should be split into multiple encoded-words');
        $this->assertSame('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる', mb_decode_mimeheader($subject_header));

        $from_header = $builded_mail->getHeaders()->get('From')->getBodyAsString();
        $this->assertSame('差出人 <from@test.local>', mb_decode_mimeheader($from_header));
    }

    public function test___construct()
    {
        $mail = new Email();
        $this->assertInstanceOf(Email::class, $mail);
        $this->assertNull($mail->getSubject());
        $this->assertNull($mail->getTextBody());
        $this->assertNull($mail->getHtmlBody());
    }

    public function test_text()
    {
        $mail  = (new Email())->html('<b>html</b>');
        $body1 = $mail->getBody();
        $this->assertSame($body1, $mail->getBody(), 'getBody() must be cached until a body-affecting setter is called');

        $mail->text('text body');
        $body2 = $mail->getBody();
        $this->assertNotSame($body1, $body2, 'text() must invalidate the cached body');
        $this->assertInstanceOf(AlternativePart::class, $body2);
    }

    public function test_html()
    {
        $mail  = (new Email())->text('text body');
        $body1 = $mail->getBody();

        $mail->html('<b>html</b>');
        $body2 = $mail->getBody();
        $this->assertNotSame($body1, $body2, 'html() must invalidate the cached body');
        $this->assertInstanceOf(AlternativePart::class, $body2);
    }

    public function test_addPart()
    {
        $mail  = (new Email())->text('text body');
        $body1 = $mail->getBody();

        $mail->addPart(new DataPart('attachment content', 'file.txt', 'text/plain'));
        $body2 = $mail->getBody();
        $this->assertNotSame($body1, $body2, 'addPart() must invalidate the cached body');
        $this->assertInstanceOf(MixedPart::class, $body2);
    }

    public function test_getBody_textOnly()
    {
        $mail = (new Email())->text('plain text');
        $body = $mail->getBody();
        $this->assertInstanceOf(TextPart::class, $body);
        $this->assertSame('plain text', $body->getBody());
    }

    public function test_getBody_htmlOnly()
    {
        $mail = (new Email())->html('<b>html</b>');
        $body = $mail->getBody();
        $this->assertInstanceOf(TextPart::class, $body);
        $this->assertSame('html', $body->getMediaSubtype());
    }

    public function test_getBody_textAndHtml_isAlternative()
    {
        $mail = (new Email())->text('plain text')->html('<b>html</b>');
        $this->assertInstanceOf(AlternativePart::class, $mail->getBody());
    }

    public function test_getBody_withAttachment_isMixed()
    {
        $mail = (new Email())->text('plain text')->attach('file content', 'file.txt', 'text/plain');
        $this->assertInstanceOf(MixedPart::class, $mail->getBody());
    }

    public function test_getBody_withInlineCidImage_isRelated()
    {
        $mail = (new Email())
            ->html('<img src="cid:logo">')
            ->embed('image bytes', 'logo', 'image/png')
        ;
        $this->assertInstanceOf(RelatedPart::class, $mail->getBody());
    }

    public function test_getBody_usesConfiguredBodyEncoder()
    {
        $mail = (new Email())->text('plain text');
        $body = $mail->getBody();
        $this->assertSame('base64', $body->getPreparedHeaders()->get('Content-Transfer-Encoding')->getBodyAsString());
    }

    public function test_getBody_usesExplicitlySetBody()
    {
        $explicit = new TextPart('explicit body');
        $mail     = new Email();
        $mail->setBody($explicit);
        $this->assertSame($explicit, $mail->getBody());
    }

    public function test_getBody_throwsWhenEmpty()
    {
        $this->expectException(LogicException::class);
        (new Email())->getBody();
    }

    public function test_send()
    {
        $mail = (new Email())
            ->subject('Subject')
            ->from('from@test.local')
            ->to('to@test.local')
            ->text('text body')
        ;
        $result = $mail->send('unittest');
        $this->assertSame($mail, $result);

        $transport = Email::transport('unittest');
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        $sent = $transport->getSentMessage();
        $this->assertNotNull($sent);
        $this->assertStringContainsString('Subject: Subject', $sent->getMessage()->toString());
        $this->assertStringContainsString(base64_encode('text body'), $sent->getMessage()->toString());
    }

    public function test_send_withMultipleEncodedHeaders()
    {
        $mail = (new Email())
            ->subject('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる')
            ->from('差出人 <from@test.local>')
            ->to('宛先 <to@test.local>', 'Tom <tom@test.local>')
            ->cc('長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる <cc@test.local>', '宛先2 <cc2@test.local>', 'CC3 <cc3@test.local>')
            ->bcc('隠しBCC <bcc@test.local>', 'BCC2 <bcc2@test.local>')
            ->replyTo('返信先 <reply@test.local>')
            ->html('<b>本文</b>')
            ->generateTextBodyFromHtml()
        ;

        $result = $mail->send('unittest');
        $this->assertSame($mail, $result);

        $transport = Email::transport('unittest');
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        $sent = $transport->getSentMessage();
        $this->assertNotNull($sent);

        // send() must dispatch the build()-ed (header-encoded) clone, not the original $mail.
        $sent_mail = $sent->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $sent_mail);
        $this->assertNotSame($mail, $sent_mail);

        $subject_header = $sent_mail->getHeaders()->get('Subject')->getBodyAsString();
        $this->assertStringNotContainsString('=?UTF-8?B??=', $subject_header, 'No empty encoded-word should ever be produced');
        $this->assertGreaterThan(1, substr_count($subject_header, '=?UTF-8?B?'), 'Long subject should be split into multiple encoded-words');
        $this->assertSame('タイトル長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる', mb_decode_mimeheader($subject_header));

        $from_header = $sent_mail->getHeaders()->get('From')->getBodyAsString();
        $this->assertSame('差出人 <from@test.local>', mb_decode_mimeheader($from_header));

        $to_header = $sent_mail->getHeaders()->get('To')->getBodyAsString();
        $this->assertStringContainsString('Tom <tom@test.local>', $to_header, 'A pure ASCII display name must be left as-is, not wrapped in an encoded-word');
        $this->assertSame('宛先 <to@test.local>, Tom <tom@test.local>', mb_decode_mimeheader($to_header));

        $cc_header = $sent_mail->getHeaders()->get('Cc')->getBodyAsString();
        $this->assertStringNotContainsString('=?UTF-8?B??=', $cc_header, 'No empty encoded-word should ever be produced');
        $this->assertStringContainsString('CC3 <cc3@test.local>', $cc_header, 'A pure ASCII display name must be left as-is, not wrapped in an encoded-word');
        $this->assertSame(
            '長い場合はどうなる長い場合はどうなる長い場合はどうなる長い場合はどうなる <cc@test.local>, 宛先2 <cc2@test.local>, CC3 <cc3@test.local>',
            mb_decode_mimeheader($cc_header)
        );

        $bcc_header = $sent_mail->getHeaders()->get('Bcc')->getBodyAsString();
        $this->assertStringContainsString('BCC2 <bcc2@test.local>', $bcc_header, 'A pure ASCII display name must be left as-is, not wrapped in an encoded-word');
        $this->assertSame('隠しBCC <bcc@test.local>, BCC2 <bcc2@test.local>', mb_decode_mimeheader($bcc_header));

        $reply_to_header = $sent_mail->getHeaders()->get('Reply-To')->getBodyAsString();
        $this->assertSame('返信先 <reply@test.local>', mb_decode_mimeheader($reply_to_header));

        // The raw wire-format message actually recorded by the transport must be consistent
        // with the above and contain no spurious empty encoded-word anywhere.
        $raw = $sent->getMessage()->toString();
        $this->assertStringNotContainsString('=?UTF-8?B??=', $raw);
        $this->assertStringContainsString('Content-Type: multipart/alternative', $raw);
    }
}
