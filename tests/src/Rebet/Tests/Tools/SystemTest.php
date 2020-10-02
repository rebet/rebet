<?php
namespace Rebet\Tests\Tools;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\System;

class SystemTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function test___callStatic()
    {
        System::emulator('mb_strlen', function (string $str, string $encoding = null) { return mb_strlen($str, $encoding ?? mb_internal_encoding()) * 2; });

        System::testing(false);
        $this->assertSame(3, System::mb_strlen('abc'));

        System::testing(true);
        $this->assertSame(6, System::mb_strlen('abc'));
    }

    public function test_headerAndHeadersList()
    {
        $this->assertSame(
            [
            ],
            System::headers_list()
        );

        System::header('Date: Thu, 30 Aug 2018 06:57:55 GMT', true, 200);
        $this->assertSame(
            [
                'HTTP/1.1 200 OK',
                'Date: Thu, 30 Aug 2018 06:57:55 GMT',
            ],
            System::headers_list()
        );

        System::header('HTTP/1.1 302 Found');
        $this->assertSame(
            [
                'HTTP/1.1 302 Found',
                'Date: Thu, 30 Aug 2018 06:57:55 GMT',
            ],
            System::headers_list()
        );

        System::header('Date: Thu, 30 Aug 2010 10:20:30 GMT', false);
        $this->assertSame(
            [
                'HTTP/1.1 302 Found',
                'Date: Thu, 30 Aug 2018 06:57:55 GMT',
                'Date: Thu, 30 Aug 2010 10:20:30 GMT',
            ],
            System::headers_list()
        );

        System::header('HTTP/1.1 200 OK');
        System::header('Date: Thu, 30 Aug 2010 10:20:30 GMT');
        $this->assertSame(
            [
                'HTTP/1.1 200 OK',
                'Date: Thu, 30 Aug 2010 10:20:30 GMT',
            ],
            System::headers_list()
        );

        System::header('Content-Type: application/json; charset=UTF-8');
        $this->assertSame(
            [
                'HTTP/1.1 200 OK',
                'Date: Thu, 30 Aug 2010 10:20:30 GMT',
                'Content-Type: application/json; charset=UTF-8',
            ],
            System::headers_list()
        );
    }

    public function test_dns_get_record()
    {
        $this->assertSame(
            [
                ["host" => "sample.local", "class" => "IN", "ttl" => 60  , "type" => "A", "ip" => "127.0.0.1"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 1, "target" => "mx.sample.local"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt1.mx.sample.local"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt2.mx.sample.local"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "TXT", "txt" => "v=spf1 mx ~all", "entries" => ["v=spf1 mx ~all"]],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "SOA", "mname" => "ns1.p01.dynect.net", "rname" => "hostmaster.sample.local", "serial" => 1234567890, "refresh" => 3600, "retry" => 600, "expire" => 604800, "minimum-ttl" => 60],
                ["host" => "sample.local", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns1.p01.dynect.net"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 836 , "type" => "NS", "target" => "ns2.p01.dynect.net"],
            ],
            System::dns_get_record('sample.local')
        );

        $this->assertSame(
            [
                ["host" => "sample.local", "class" => "IN", "ttl" => 60  , "type" => "A", "ip" => "127.0.0.1"],
            ],
            System::dns_get_record('sample.local', DNS_A | DNS_AAAA)
        );

        $this->assertSame(
            [
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 1, "target" => "mx.sample.local"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt1.mx.sample.local"],
                ["host" => "sample.local", "class" => "IN", "ttl" => 3600, "type" => "MX", "pri" => 5, "target" => "alt2.mx.sample.local"],
            ],
            System::dns_get_record('sample.local', DNS_MX)
        );

        $this->assertSame(
            [],
            System::dns_get_record('invalid.local', DNS_A | DNS_AAAA)
        );
    }
}
