<?php
namespace Rebet\Tests\Application\Bootstrap;

use Exception;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Http\HttpKernel;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Template\Letterpress;

use function PHPUnit\Framework\assertSame;

class LetterpressTagCustomizerTest extends RebetTestCase
{
    public function dataBootstrapWithHttpKernels() : array
    {
        $own          = new User();
        $own->user_id = 2;

        $others          = new User();
        $others->user_id = 3;

        return [
            // env/envnot
            ["unittest" , "{% env 'unittest' %}unittest{% else %}else{% endenv %}"],
            ["unittest" , "{% env 'local' %}local{% elseenv 'unittest' %}unittest{% else %}else{% endenv %}"],
            ["else"     , "{% env 'local' %}local{% else %}else{% endenv %}"],
            ["else"     , "{% envnot 'unittest' %}not unittest{% else %}else{% endenvnot %}"],
            ["not local", "{% envnot 'local' %}not local{% else %}else{% endenvnot %}"],

            // prefix
            ["/prefix", "{% prefix %}"],

            // role/rolenot
            ["user"     , "{% role 'user' %}user{% else %}else{% endrole %}"],
            ["user"     , "{% role 'admin' %}admin{% elserole 'user' %}user{% else %}else{% endrole %}"],
            ["else"     , "{% role 'admin' %}admin{% else %}else{% endrole %}"],
            ["else"     , "{% rolenot 'user' %}not user{% else %}else{% endrolenot %}"],
            ["not admin", "{% rolenot 'admin' %}not admin{% else %}else{% endrolenot %}"],

            // can/cannot
            ["can update"    , "{% can 'update', \$user %}can update{% endcan %}", ['user' => $own]],
            [""              , "{% can 'update', \$user %}can update{% endcan %}", ['user' => $others]],
            ["can create"    , "{% can 'create', '@mock\\Address', \$addresses %}can create{% else %}can not create{% endcan %}", ['addresses' => [1, 2, 3]]],
            ["can not create", "{% can 'create', '@mock\\Address', \$addresses %}can create{% else %}can not create{% endcan %}", ['addresses' => [1, 2, 3, 4, 5]]],

            // lang
            ["Hello, Jhon."       , "{% lang 'message.welcome', ['name' => 'Jhon'] %}"],
            ["Hello, Jhon."       , "{% lang 'message.welcome', ['name' => \$name] %}", ['name' => 'Jhon']],
            ["Hello, Jhon."       , "{% lang 'message.welcome', \$user %}", ['user' => ['name' => 'Jhon']]],
            ["ようこそ、太郎様"    , "{% lang 'message.welcome', ['name' => \$name], 'locale' => \$locale %}", ['name' => '太郎', 'locale' => 'ja']],
            ["This is an apple."  , "{% lang 'message.sample', ['amount' => \$amount], \$amount %}", ['amount' => 1]],
            ["There are 3 apples.", "{% lang 'message.sample', ['amount' => \$amount], \$amount %}", ['amount' => 3]],
       ];
    }
    
    /**
     * @dataProvider dataBootstrapWithHttpKernels
     */
    public function test_bootstrap_withHttpKernel($expect, $template, $params = [])
    {
        $request = $this->createRequestMock('/', 'user', 'web', 'web', 'GET', '/prefix');
        $this->signin($request);
        $kernel  = $this->createMock(HttpKernel::class);
        $kernel->method('request')->willReturn($request);

        Letterpress::clear();
        $bootstrapper = new LetterpressTagCustomizer();
        $bootstrapper->bootstrap($kernel);

        $letterpress = new Letterpress($template);
        assertSame($expect, $letterpress->with($params)->render());
    }
}
