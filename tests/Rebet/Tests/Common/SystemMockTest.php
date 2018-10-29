<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\System;

/**
 * 本テストは tests/mocks.php にて定義されている Systemモック クラスのテストとなります。
 */
class SystemMockTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_mock_init()
    {
        $this->assertEmpty(System::$header_raw_arges);
        $this->assertSame(['HTTP/1.1 200 OK'], System::headers_list());
        System::header('Content-Type: application/javascript; charset=UTF-8');
        $this->assertNotEmpty(System::$header_raw_arges);
        $this->assertNotSame(['HTTP/1.1 200 OK'], System::headers_list());
        System::initMock();
        $this->assertEmpty(System::$header_raw_arges);
        $this->assertSame(['HTTP/1.1 200 OK'], System::headers_list());
    }

    /**
     * @expectedException Rebet\Tests\ExitException
     * @expectedExceptionMessage Exit message
     */
    public function test_exit()
    {
        System::exit('Exit message');
        $this->fail('Never execute.');
    }

    /**
     * @expectedException Rebet\Tests\DieException
     * @expectedExceptionMessage Die message
     */
    public function test_die()
    {
        System::die('Die message');
        $this->fail('Never execute.');
    }

    public function test_header_rawArges()
    {
        $this->assertEmpty(System::$header_raw_arges);
        System::header('Content-Type: application/javascript; charset=UTF-8');
        $this->assertSame(
            [
                [
                    'header'             => 'Content-Type: application/javascript; charset=UTF-8',
                    'replace'            => true,
                    'http_response_code' => null
                ],
            ],
            System::$header_raw_arges
        );
        System::header('Date: Thu, 30 Aug 2018 06:57:55 GMT', false, 200);
        $this->assertSame(
            [
                [
                    'header'             => 'Content-Type: application/javascript; charset=UTF-8',
                    'replace'            => true,
                    'http_response_code' => null
                ],
                [
                    'header'             => 'Date: Thu, 30 Aug 2018 06:57:55 GMT',
                    'replace'            => false,
                    'http_response_code' => 200
                ],
            ],
            System::$header_raw_arges
        );
    }

    public function test_header()
    {
        $this->assertSame(
            [
                'HTTP/1.1 200 OK'
            ],
            System::headers_list()
        );

        System::header('HTTP/1.1 302 Found');
        $this->assertSame(
            [
                'HTTP/1.1 302 Found'
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

        System::header('Date: Thu, 30 Aug 2010 10:20:30 GMT', false);
        $this->assertSame(
            [
                'HTTP/1.1 200 OK',
                'Date: Thu, 30 Aug 2018 06:57:55 GMT',
                'Date: Thu, 30 Aug 2010 10:20:30 GMT',
            ],
            System::headers_list()
        );

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
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "SOA", "mname" => "ns1.p16.dynect.net", "rname" => "hostmaster.github.com", "serial" => 1540354846,"refresh" => 3600,"retry" => 600,"expire" => 604800,"minimum-ttl" => 60],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns-520.awsdns-01.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns-421.awsdns-52.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns4.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns-1707.awsdns-21.co.uk"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns3.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns-1283.awsdns-32.org"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns2.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 836 ,"type" => "NS", "target" => "ns1.p16.dynect.net"],
                ["host" => "github.com", "class" => "IN", "ttl" => 60  ,"type" => "A", "ip" => "192.30.253.112"],
                ["host" => "github.com", "class" => "IN", "ttl" => 60  ,"type" => "A", "ip" => "192.30.253.113"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "MX", "pri" => 10,"target" => "ALT4.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "MX", "pri" => 1,"target" => "ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "MX", "pri" => 5,"target" => "ALT1.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "MX", "pri" => 10,"target" => "ALT3.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "MX", "pri" => 5,"target" => "ALT2.ASPMX.L.GOOGLE.com"],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "TXT", "txt" => "MS=ms44452932", "entries" => ["MS=ms44452932"]],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "TXT", "txt" => "MS=6BF03E6AF5CB689E315FB6199603BABF2C88D805", "entries" => ["MS=6BF03E6AF5CB689E315FB6199603BABF2C88D805"]],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "TXT", "txt" => "v=spf1 ip4:192.30.252.0\/22 ip4:208.74.204.0\/22 ip4:46.19.168.0\/23 include:_spf.google.com include:esp.github.com include:_spf.createsend.com include:mail.zendesk.com include:servers.mcsv.net ~all", "entries" => ["v=spf1 ip4:192.30.252.0\/22 ip4:208.74.204.0\/22 ip4:46.19.168.0\/23 include:_spf.google.com include:esp.github.com include:_spf.createsend.com include:mail.zendesk.com include:servers.mcsv.net ~all"]],
                ["host" => "github.com", "class" => "IN", "ttl" => 3600,"type" => "TXT", "txt" => "docusign=087098e3-3d46-47b7-9b4e-8a23028154cd", "entries" => ["docusign=087098e3-3d46-47b7-9b4e-8a23028154cd"]],
            ],
            System::dns_get_record('github.com')
        );

        $this->assertSame(
            [
                ["host" => "github.com","class" => "IN","ttl" => 60  ,"type" => "A","ip" => "192.30.253.112"],
                ["host" => "github.com","class" => "IN","ttl" => 60  ,"type" => "A","ip" => "192.30.253.113"],
            ],
            System::dns_get_record('github.com', DNS_A | DNS_AAAA)
        );

        $this->assertSame(
            [],
            System::dns_get_record('invalid.local', DNS_A | DNS_AAAA)
        );
    }
}
