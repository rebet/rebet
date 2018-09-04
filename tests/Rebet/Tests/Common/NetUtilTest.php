<?php
namespace Rebet\Tests\Common;

use PHPUnit\Framework\TestCase;
use Rebet\Common\SecurityUtil;
use Rebet\Common\NetUtil;

class NetUtilTest extends TestCase {
    public function test_encodeBase64Url() {
        foreach ([
            // inclued '='
            'Test'
                => 'VGVzdA--',
            // inclued '+/'
            '貴方がたの人間性を心にとどめ、そして他のことを忘れよ。困難の中に、機会がある。情報は知識にあらず。 - アインシュタイン'
                => '6LK05pa544GM44Gf44Gu5Lq66ZaT5oCn44KS5b.D44Gr44Go44Gp44KB44CB44Gd44GX44Gm5LuW44Gu44GT44Go44KS5b.Y44KM44KI44CC5Zuw6Zuj44Gu5Lit44Gr44CB5qmf5Lya44GM44GC44KL44CC5oOF5aCx44Gv55.l6K2Y44Gr44GC44KJ44Ga44CCIC0g44Ki44Kk44Oz44K344Ol44K_44Kk44Oz'
        ] as $plain => $encoded) {
            $this->assertSame($encoded, NetUtil::encodeBase64Url($plain));
        }

        for ($i=0; $i < 100; $i++) { 
            $plain   = SecurityUtil::randomCode(mt_rand(12, 32));
            $encoded = NetUtil::encodeBase64Url($plain);
            $decoded = NetUtil::decodeBase64Url($encoded);
            $this->assertSame($plain, $decoded);
        }
    }

    public function test_decodeBase64Url() {
        foreach ([
            // inclued '='
            'Test'
                => 'VGVzdA--',
            // inclued '+/'
            '貴方がたの人間性を心にとどめ、そして他のことを忘れよ。困難の中に、機会がある。情報は知識にあらず。 - アインシュタイン'
                => '6LK05pa544GM44Gf44Gu5Lq66ZaT5oCn44KS5b.D44Gr44Go44Gp44KB44CB44Gd44GX44Gm5LuW44Gu44GT44Go44KS5b.Y44KM44KI44CC5Zuw6Zuj44Gu5Lit44Gr44CB5qmf5Lya44GM44GC44KL44CC5oOF5aCx44Gv55.l6K2Y44Gr44GC44KJ44Ga44CCIC0g44Ki44Kk44Oz44K344Ol44K_44Kk44Oz'
        ] as $plain => $encoded) {
            $this->assertSame($plain, NetUtil::decodeBase64Url($encoded));
        }

        for ($i=0; $i < 100; $i++) { 
            $plain   = SecurityUtil::randomCode(mt_rand(12, 32));
            $encoded = NetUtil::encodeBase64Url($plain);
            $decoded = NetUtil::decodeBase64Url($encoded);
            $this->assertSame($plain, $decoded);
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function test_redirect() {
        ob_start();
        NetUtil::redirect('https://github.com/rebet/rebet');
        $this->assertTrue(true, 'This is running');
        ob_end_clean();

        // $headers_list = xdebug_get_headers();
        // $this->assertContains('HTTP/1.1 302 Found', $headers_list);
        // $this->assertContains('Location: https://github.com/rebet/rebet', $headers_list);
        $this->markTestIncomplete('We should test about header() output.');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_redirect_withParam() {
        ob_start();
        $url = NetUtil::redirect('https://www.google.com/search', ['q' => 'rebet']);
        $this->assertTrue(true, 'This is running');
        ob_end_clean();

        // $headers_list = xdebug_get_headers();
        // $this->assertContains('HTTP/1.1 302 Found', $headers_list);
        // $this->assertContains('Location: https://www.google.com/search?q=rebet', $headers_list);
        $this->markTestIncomplete('We should test about header() output.');
    }
}
