<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Nets;

use Rebet\Tests\RebetTestCase;

class NetsTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function test_encodeBase64Url()
    {
        foreach ([
            // inclued '='
            'Test'
            => 'VGVzdA--',
            // inclued '+/'
            '貴方がたの人間性を心にとどめ、そして他のことを忘れよ。困難の中に、機会がある。情報は知識にあらず。 - アインシュタイン'
            => '6LK05pa544GM44Gf44Gu5Lq66ZaT5oCn44KS5b.D44Gr44Go44Gp44KB44CB44Gd44GX44Gm5LuW44Gu44GT44Go44KS5b.Y44KM44KI44CC5Zuw6Zuj44Gu5Lit44Gr44CB5qmf5Lya44GM44GC44KL44CC5oOF5aCx44Gv55.l6K2Y44Gr44GC44KJ44Ga44CCIC0g44Ki44Kk44Oz44K344Ol44K_44Kk44Oz'
        ] as $plain => $encoded) {
            $this->assertSame($encoded, Nets::encodeBase64Url($plain));
        }

        for ($i=0; $i < 100; $i++) {
            $plain   = $this->_randomCode(12, 32);
            $encoded = Nets::encodeBase64Url($plain);
            $decoded = Nets::decodeBase64Url($encoded);
            $this->assertSame($plain, $decoded);
        }
    }

    public function test_decodeBase64Url()
    {
        foreach ([
            // inclued '='
            'Test'
            => 'VGVzdA--',
            // inclued '+/'
            '貴方がたの人間性を心にとどめ、そして他のことを忘れよ。困難の中に、機会がある。情報は知識にあらず。 - アインシュタイン'
            => '6LK05pa544GM44Gf44Gu5Lq66ZaT5oCn44KS5b.D44Gr44Go44Gp44KB44CB44Gd44GX44Gm5LuW44Gu44GT44Go44KS5b.Y44KM44KI44CC5Zuw6Zuj44Gu5Lit44Gr44CB5qmf5Lya44GM44GC44KL44CC5oOF5aCx44Gv55.l6K2Y44Gr44GC44KJ44Ga44CCIC0g44Ki44Kk44Oz44K344Ol44K_44Kk44Oz'
        ] as $plain => $encoded) {
            $this->assertSame($plain, Nets::decodeBase64Url($encoded));
        }

        for ($i=0; $i < 100; $i++) {
            $plain   = $this->_randomCode(12, 32);
            $encoded = Nets::encodeBase64Url($plain);
            $decoded = Nets::decodeBase64Url($encoded);
            $this->assertSame($plain, $decoded);
        }
    }

    public function test_urlGetContents()
    {
        $content = Nets::urlGetContents('https://raw.githubusercontent.com/rebet/rebet/master/LICENSE');
        $this->assertMatchesRegularExpression('/^MIT License/', $content);
        $this->assertMatchesRegularExpression('/github.com\/rebet/', $content);
    }
}
