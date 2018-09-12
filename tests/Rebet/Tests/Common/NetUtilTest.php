<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\NetUtil;

use Rebet\Common\System;

class NetUtilTest extends RebetTestCase {
    public function setUp() {
        System::mock_init();
    }

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
            $plain   = $this->_randomCode(12, 32);
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
            $plain   = $this->_randomCode(12, 32);
            $encoded = NetUtil::encodeBase64Url($plain);
            $decoded = NetUtil::decodeBase64Url($encoded);
            $this->assertSame($plain, $decoded);
        }
    }

    /**
     * @expectedException Rebet\Tests\ExitException
     */
    public function test_redirect() {
        try {
            NetUtil::redirect('https://github.com/rebet/rebet');
            $this->fail('Never executed.');
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.1 302 Found', $headers);
            $this->assertContains('Location: https://github.com/rebet/rebet', $headers);
        }
    }

    /**
     * @expectedException Rebet\Tests\ExitException
     */
    public function test_redirect_withParam() {
        try {
            $url = NetUtil::redirect('https://www.google.com/search', ['q' => 'github rebet']);
            $this->fail('Never executed.');
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.1 302 Found', $headers);
            $this->assertContains('Location: https://www.google.com/search?q=github+rebet', $headers);
        }
    }

    /**
     * @expectedException Rebet\Tests\ExitException
     */
    public function test_redirect_withParamBoth() {
        try {
            $url = NetUtil::redirect('https://www.google.com/search?oe=utf-8', ['q' => 'github rebet']);
            $this->fail('Never executed.');
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.1 302 Found', $headers);
            $this->assertContains('Location: https://www.google.com/search?oe=utf-8&q=github+rebet', $headers);
        }
    }

    /**
     * @expectedException Rebet\Tests\ExitException
     */
    public function test_json() {
        ob_start();
        try {
            NetUtil::json(['name' => 'John', 'hobbies' => ['game', 'outdoor']]);
            $this->fail('Never executed.');
        } finally {
            $this->assertSame('{"name":"John","hobbies":["game","outdoor"]}', ob_get_contents());
            ob_end_clean();
        
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.1 200 OK', $headers);
            $this->assertContains('Content-Type: application/json; charset=UTF-8', $headers);
        }
    }

    /**
     * @expectedException Rebet\Tests\ExitException
     */
    public function test_jsonp() {
        ob_start();
        try {
            NetUtil::jsonp(['name' => 'John', 'hobbies' => ['game', 'outdoor']], 'callback');
            $this->fail('Never executed.');
        } finally {
            $this->assertSame('callback({"name":"John","hobbies":["game","outdoor"]})', ob_get_contents());
            ob_end_clean();
            
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.1 200 OK', $headers);
            $this->assertContains('Content-Type: application/javascript; charset=UTF-8', $headers);
        }
    }

    public function test_urlGetContents() {
        $content = NetUtil::urlGetContents('https://raw.githubusercontent.com/rebet/rebet/master/LICENSE');
        $this->assertRegExp('/^MIT License/', $content);
        $this->assertRegExp('/github.com\/rebet/', $content);
    }
}
