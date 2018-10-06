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
}
